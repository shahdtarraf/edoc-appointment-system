<?php

function edoc_assistant_widget()
{
    if (!function_exists('edoc_csrf_token')) {
        require_once dirname(__DIR__) . '/csrf.php';
    }
    $prefix = edoc_prefix();
    $lang = edoc_lang();
    $role = 'guest';
    if (isset($_SESSION['usertype']) && in_array($_SESSION['usertype'], array('p', 'd', 'a'), true) && !empty($_SESSION['user'])) {
        $role = $_SESSION['usertype'];
    }
    if ($lang === 'en') {
        $welcome = 'I help you use eDoc Demo: accounts, specialties, doctors, and simulated booking. I am not a clinician and I cannot book for you.';
        $placeholder = 'Ask how to use the platform…';
        $send = 'Send';
        $close = 'Close';
        $reset = 'New chat';
        $open = 'Help';
        $min = 'Minimise';
        $typing = 'Writing…';
        $error = 'The assistant is unavailable. Use the menus to continue.';
        $copy = 'Copy';
        $intro = 'Suggested questions';
        $chips = array(
            'guest' => array('How do I create an account?', 'How do I log in?', 'What is eDoc?'),
            'p' => array('How do I book a session?', 'How do I find a doctor?', 'How do I use AI features?', 'Where are my appointments?'),
            'd' => array('How does the doctor dashboard work?', 'Where are my sessions?'),
            'a' => array('How do I add a doctor?', 'How do I add a session?', 'How are facilities managed?'),
        );
    } else {
        $welcome = 'أساعدك في استخدام eDoc التجريبية: الحساب، التخصصات، الأطباء، والحجز المحاكى. لست طبيباً ولا أحجز نيابة عنك.';
        $placeholder = 'اسأل عن استخدام المنصة…';
        $send = 'إرسال';
        $close = 'إغلاق';
        $reset = 'محادثة جديدة';
        $open = 'مساعد';
        $min = 'تصغير';
        $typing = 'يكتب…';
        $error = 'المساعد غير متاح حالياً. يمكنك المتابعة من القوائم.';
        $copy = 'نسخ';
        $intro = 'أسئلة مقترحة';
        $chips = array(
            'guest' => array('كيف أنشئ حساباً؟', 'كيف أسجل الدخول؟', 'ما هي eDoc؟'),
            'p' => array('كيف أحجز موعداً؟', 'كيف أجد طبيباً؟', 'كيف أستخدم ميزات الذكاء الاصطناعي؟', 'كيف أراجع حجوزاتي؟'),
            'd' => array('كيف أستخدم لوحة الطبيب؟', 'أين جلساتي؟'),
            'a' => array('كيف أضيف طبيباً؟', 'كيف أضيف جلسة؟', 'كيف تُدار المنشآت؟'),
        );
    }
    $examples = isset($chips[$role]) ? $chips[$role] : $chips['guest'];
    echo '<div id="edoc-assistant" data-endpoint="' . edoc_h($prefix) . 'assistant-chat.php" data-csrf="' . edoc_h(edoc_csrf_token()) . '" data-welcome="' . edoc_h($welcome) . '" data-error="' . edoc_h($error) . '" data-copy="' . edoc_h($copy) . '" data-intro="' . edoc_h($intro) . '">';
    echo '<button type="button" class="edoc-assist-btn" aria-label="' . edoc_h($open) . '">' . edoc_h($open) . '</button>';
    echo '<div class="edoc-assist-panel" role="dialog" aria-label="' . edoc_h($open) . '">';
    echo '<div class="edoc-assist-head"><div><strong>' . edoc_h($open) . '</strong><span class="edoc-assist-sub">' . edoc_h($lang === 'en' ? 'Platform guide' : 'دليل استخدام المنصة') . '</span></div><span class="edoc-assist-tools">';
    echo '<button type="button" data-reset>' . edoc_h($reset) . '</button> ';
    echo '<button type="button" data-min>' . edoc_h($min) . '</button> ';
    echo '<button type="button" data-close>' . edoc_h($close) . '</button></span></div>';
    echo '<div class="edoc-assist-body"></div>';
    echo '<p class="edoc-assist-typing">' . edoc_h($typing) . '</p>';
    echo '<div class="edoc-assist-chips">';
    foreach ($examples as $q) {
        echo '<button type="button" data-q="' . edoc_h($q) . '">' . edoc_h($q) . '</button>';
    }
    echo '</div>';
    echo '<form class="edoc-assist-form"><textarea name="message" maxlength="800" rows="2" placeholder="' . edoc_h($placeholder) . '"></textarea>';
    echo '<button type="submit">' . edoc_h($send) . '</button></form></div></div>';
    echo '<script src="' . edoc_h($prefix) . 'js/edoc-assistant.js" defer></script>';
}
