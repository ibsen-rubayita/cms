// Small helpers for the site.

// 1. Mobile menu button
var navToggle = document.getElementById("navToggle");
var navLinks = document.getElementById("navLinks");
if (navToggle) {
    navToggle.addEventListener("click", function () {
        var isOpen = navLinks.classList.toggle("open");
        navToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
}
if (navLinks) {
    navLinks.addEventListener("click", function (event) {
        if (event.target.closest("a") && navToggle) {
            navLinks.classList.remove("open");
            navToggle.setAttribute("aria-expanded", "false");
        }
    });
}

// 2. Close buttons on message boxes
var closeButtons = document.querySelectorAll(".alert-close");
for (var i = 0; i < closeButtons.length; i++) {
    closeButtons[i].addEventListener("click", function () {
        this.parentNode.style.display = "none";
    });
}

// 3. Ask "Are you sure?" before forms marked with data-confirm
var confirmForms = document.querySelectorAll("form[data-confirm]");
for (var j = 0; j < confirmForms.length; j++) {
    confirmForms[j].addEventListener("submit", function (event) {
        if (!confirm(this.getAttribute("data-confirm"))) {
            event.preventDefault();
        }
    });
}

// 4. Show or hide a password
var toggles = document.querySelectorAll("[data-toggle-password]");
for (var k = 0; k < toggles.length; k++) {
    toggles[k].addEventListener("click", function () {
        var field = document.getElementById(this.getAttribute("data-toggle-password"));
        if (field.type == "password") {
            field.type = "text";
            this.textContent = "Hide";
        } else {
            field.type = "password";
            this.textContent = "Show";
        }
    });
}

// 5. Live character counter for text areas marked with data-counter
var areas = document.querySelectorAll("textarea[data-counter]");
for (var m = 0; m < areas.length; m++) {
    var counter = document.getElementById(areas[m].getAttribute("data-counter"));
    var update = function (area, box) {
        box.textContent = area.value.length + " characters";
    };
    update(areas[m], counter);
    areas[m].addEventListener("input", (function (area, box) {
        return function () { update(area, box); };
    })(areas[m], counter));
}
