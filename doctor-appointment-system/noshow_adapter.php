<?php

function edoc_noshow_ensure_table($database)
{
    if (!($database instanceof mysqli)) {
        return false;
    }
    $sql = "CREATE TABLE IF NOT EXISTS ai_noshow_shadow (
        shadow_id INT NOT NULL AUTO_INCREMENT,
        appoid INT NOT NULL,
        status VARCHAR(32) NOT NULL,
        skip_reason VARCHAR(255) DEFAULT NULL,
        raw_score DOUBLE DEFAULT NULL,
        calibrated_score DOUBLE DEFAULT NULL,
        risk_band VARCHAR(32) DEFAULT NULL,
        recommended_admin_action VARCHAR(160) DEFAULT NULL,
        model_version VARCHAR(80) DEFAULT NULL,
        ood_warnings VARCHAR(512) DEFAULT NULL,
        scored_at DATETIME NOT NULL,
        PRIMARY KEY (shadow_id),
        KEY appoid (appoid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return $database->query($sql) === true;
}

function edoc_noshow_dow_monday0(DateTime $dt)
{
    return ((int) $dt->format('N')) - 1;
}

function edoc_noshow_derive_features($database, $appoid)
{
    $order = array(
        'WaitingDays',
        'BookingDayOfWeek',
        'AppointmentDayOfWeek',
        'BookingMonth',
        'AppointmentMonth',
        'IsSameDayAppointment',
        'IsWeekendAppointment',
        'BookingHour',
        'Age',
        'Gender',
    );
    $features = array();
    foreach ($order as $name) {
        $features[$name] = null;
    }
    $sources = array();
    $missing = array();
    $appoid = (int) $appoid;

    $sql = 'SELECT appointment.appoid, appointment.appodate,
                   schedule.scheduledate, schedule.scheduletime,
                   patient.pdob
            FROM appointment
            INNER JOIN schedule ON appointment.scheduleid = schedule.scheduleid
            INNER JOIN patient ON appointment.pid = patient.pid
            WHERE appointment.appoid = ? LIMIT 1';
    $stmt = $database->prepare($sql);
    if ($stmt === false) {
        return array(
            'ok' => false,
            'missing' => array('query'),
            'features' => $features,
            'sources' => array(),
            'appoid' => $appoid,
        );
    }
    $stmt->bind_param('i', $appoid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return array(
            'ok' => false,
            'missing' => array('appointment'),
            'features' => $features,
            'sources' => array(),
            'appoid' => $appoid,
        );
    }

    $bookDate = isset($row['appodate']) ? trim((string) $row['appodate']) : '';
    $apptDate = isset($row['scheduledate']) ? trim((string) $row['scheduledate']) : '';

    $sources['booking_date'] = 'appointment.appodate — calendar date written at insert (booking-complete.php uses date()). DATE only; no clock time.';
    $sources['appointment_date'] = 'schedule.scheduledate — session/appointment day, not the booking timestamp.';
    $sources['appointment_clock'] = 'schedule.scheduletime — appointment slot time; not used as BookingHour.';

    if ($bookDate === '') {
        $missing[] = 'WaitingDays';
        $missing[] = 'BookingDayOfWeek';
        $missing[] = 'BookingMonth';
        $missing[] = 'IsSameDayAppointment';
    }
    if ($apptDate === '') {
        $missing[] = 'AppointmentDayOfWeek';
        $missing[] = 'AppointmentMonth';
        $missing[] = 'IsWeekendAppointment';
        $missing[] = 'WaitingDays';
        $missing[] = 'IsSameDayAppointment';
    }

    if ($bookDate !== '' && $apptDate !== '') {
        $b = new DateTime($bookDate);
        $a = new DateTime($apptDate);
        $features['WaitingDays'] = (int) $b->diff($a)->format('%r%a');
        $features['BookingDayOfWeek'] = edoc_noshow_dow_monday0($b);
        $features['AppointmentDayOfWeek'] = edoc_noshow_dow_monday0($a);
        $features['BookingMonth'] = (int) $b->format('n');
        $features['AppointmentMonth'] = (int) $a->format('n');
        $features['IsSameDayAppointment'] = ($bookDate === $apptDate) ? 1 : 0;
        $dow = $features['AppointmentDayOfWeek'];
        $features['IsWeekendAppointment'] = ($dow === 5 || $dow === 6) ? 1 : 0;
        $sources['WaitingDays'] = 'appointment.appodate vs schedule.scheduledate (pandas-compatible Monday=0 for weekdays)';
        $sources['BookingDayOfWeek'] = 'appointment.appodate, Monday=0';
        $sources['AppointmentDayOfWeek'] = 'schedule.scheduledate, Monday=0';
        $sources['BookingMonth'] = 'appointment.appodate month 1-12';
        $sources['AppointmentMonth'] = 'schedule.scheduledate month 1-12';
        $sources['IsSameDayAppointment'] = 'appodate == scheduledate';
        $sources['IsWeekendAppointment'] = 'AppointmentDayOfWeek in {5,6} (Sat/Sun), matching training mapping';
    }

    $missing[] = 'BookingHour';
    $sources['BookingHour'] = 'unavailable: eDoc stores booking as DATE (appodate) with no booking clock; scheduletime is the visit slot, not used';

    if (empty($row['pdob'])) {
        $missing[] = 'Age';
        $sources['Age'] = 'patient.pdob missing';
    } else {
        try {
            $dob = new DateTime((string) $row['pdob']);
            $ref = $bookDate !== '' ? new DateTime($bookDate) : new DateTime($apptDate);
            $features['Age'] = (int) $dob->diff($ref)->y;
            $sources['Age'] = 'patient.pdob vs booking date (appodate) when present';
        } catch (Exception $e) {
            $missing[] = 'Age';
            $sources['Age'] = 'patient.pdob unparseable';
        }
    }

    $missing[] = 'Gender';
    $sources['Gender'] = 'unavailable: Demo patient table has no gender column; null is passed so the original pipeline imputer may fill it. PHP does not substitute F/M.';

    $missing = array_values(array_unique($missing));
    $canCall = ($features['WaitingDays'] !== null && $features['BookingDayOfWeek'] !== null && $features['AppointmentDayOfWeek'] !== null);

    return array(
        'ok' => $canCall,
        'missing' => $missing,
        'features' => $features,
        'sources' => $sources,
        'appoid' => $appoid,
    );
}
