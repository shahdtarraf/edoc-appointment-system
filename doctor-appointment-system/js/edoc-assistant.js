(function () {
  var root = document.getElementById("edoc-assistant");
  if (!root) return;
  var endpoint = root.getAttribute("data-endpoint");
  var csrf = root.getAttribute("data-csrf");
  var panel = root.querySelector(".edoc-assist-panel");
  var toggle = root.querySelector(".edoc-assist-btn");
  var closeBtn = root.querySelector("[data-close]");
  var minBtn = root.querySelector("[data-min]");
  var resetBtn = root.querySelector("[data-reset]");
  var form = root.querySelector("form");
  var input = root.querySelector("textarea[name='message'], input[name='message']");
  var body = root.querySelector(".edoc-assist-body");
  var typing = root.querySelector(".edoc-assist-typing");
  var sendBtn = form.querySelector("button[type='submit']");
  var welcome = root.getAttribute("data-welcome") || "";
  var errMsg = root.getAttribute("data-error") || "";
  var copyLbl = root.getAttribute("data-copy") || "Copy";
  var history = [];
  var busy = false;

  function renderSafe(text) {
    var wrap = document.createElement("div");
    var lines = String(text || "").split(/\n/);
    var list = null;
    var listType = "";
    function closeList() {
      if (list) wrap.appendChild(list);
      list = null;
      listType = "";
    }
    lines.forEach(function (raw) {
      var line = raw.replace(/\s+$/, "");
      var ol = line.match(/^\s*\d+[\.)]\s+(.*)$/);
      var ul = line.match(/^\s*[-*]\s+(.*)$/);
      if (ol) {
        if (listType !== "ol") {
          closeList();
          list = document.createElement("ol");
          listType = "ol";
        }
        var li = document.createElement("li");
        li.textContent = ol[1];
        list.appendChild(li);
        return;
      }
      if (ul) {
        if (listType !== "ul") {
          closeList();
          list = document.createElement("ul");
          listType = "ul";
        }
        var li2 = document.createElement("li");
        li2.textContent = ul[1];
        list.appendChild(li2);
        return;
      }
      closeList();
      if (!line) return;
      var p = document.createElement("p");
      p.style.margin = "0 0 6px";
      p.textContent = line;
      wrap.appendChild(p);
    });
    closeList();
    return wrap;
  }

  function add(role, text, source, followups) {
    var div = document.createElement("div");
    div.className = "edoc-assist-msg " + role;
    if (role === "bot") {
      div.appendChild(renderSafe(text));
      if (source) {
        var s = document.createElement("span");
        s.className = "edoc-assist-src";
        s.textContent = source;
        div.appendChild(s);
      }
      var copy = document.createElement("button");
      copy.type = "button";
      copy.className = "edoc-assist-copy";
      copy.textContent = copyLbl;
      copy.addEventListener("click", function () {
        if (navigator.clipboard) navigator.clipboard.writeText(text);
      });
      div.appendChild(copy);
    } else {
      div.textContent = text;
    }
    body.appendChild(div);
    if (followups && followups.length) {
      var row = document.createElement("div");
      row.className = "edoc-assist-chips";
      followups.forEach(function (q) {
        var b = document.createElement("button");
        b.type = "button";
        b.textContent = q;
        b.addEventListener("click", function () {
          input.value = q;
          form.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
        });
        row.appendChild(b);
      });
      body.appendChild(row);
    }
    body.scrollTop = body.scrollHeight;
  }

  function reset() {
    history = [];
    body.innerHTML = "";
    add("bot", welcome);
    var chips = root.querySelector(".edoc-assist-chips");
    if (chips) body.appendChild(chips);
  }

  function openPanel() {
    panel.classList.add("open");
    if (body.childElementCount === 0) reset();
  }

  toggle.addEventListener("click", function () {
    if (panel.classList.contains("open")) panel.classList.remove("open");
    else openPanel();
  });
  closeBtn.addEventListener("click", function () { panel.classList.remove("open"); });
  if (minBtn) minBtn.addEventListener("click", function () { panel.classList.remove("open"); });
  resetBtn.addEventListener("click", reset);

  root.querySelectorAll("[data-q]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      input.value = btn.getAttribute("data-q");
      form.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
    });
  });

  input.addEventListener("keydown", function (ev) {
    if (ev.key === "Enter" && !ev.shiftKey) {
      ev.preventDefault();
      form.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
    }
  });

  form.addEventListener("submit", function (ev) {
    ev.preventDefault();
    if (busy) return;
    var msg = (input.value || "").trim();
    if (!msg) return;
    add("user", msg);
    history.push({ role: "user", text: msg.slice(0, 400) });
    if (history.length > 8) history = history.slice(-8);
    input.value = "";
    busy = true;
    sendBtn.disabled = true;
    typing.style.display = "block";
    fetch(endpoint, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message: msg, csrf_token: csrf, history: history.slice(0, -1) })
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        typing.style.display = "none";
        busy = false;
        sendBtn.disabled = false;
        var ans = (res.j && res.j.answer) ? res.j.answer : errMsg;
        var src = res.j && res.j.source_label ? res.j.source_label : "";
        var fup = res.j && res.j.followups ? res.j.followups : [];
        add("bot", ans, src, fup);
        history.push({ role: "assistant", text: String(ans).slice(0, 400) });
        if (history.length > 8) history = history.slice(-8);
      })
      .catch(function () {
        typing.style.display = "none";
        busy = false;
        sendBtn.disabled = false;
        add("bot", errMsg);
      });
  });
})();
