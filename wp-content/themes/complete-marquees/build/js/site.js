(function () {
  var mobileNav = document.getElementById("mobile__nav");
  if (mobileNav) {
    var triggers = document.querySelectorAll(".mobile__nav__trigger");
    for (var i = 0; i < triggers.length; i++) {
      triggers[i].addEventListener("click", function () {
        mobileNav.classList.add("is-open");
      });
    }
    var closers = document.querySelectorAll(".mobile__nav__close");
    for (var j = 0; j < closers.length; j++) {
      closers[j].addEventListener("click", function () {
        mobileNav.classList.remove("is-open");
      });
    }
  }

  var sliderBg = document.querySelector(".slider-bg");
  if (sliderBg) {
    var cards = [
      ["wedding__marquees", "wedding-bg"],
      ["party__marquees", "party-bg"],
      ["event__marquees", "event-bg"],
      ["special__marquees", "special-bg"]
    ];
    var allBg = ["default-bg", "wedding-bg", "party-bg", "event-bg", "special-bg"];
    for (var k = 0; k < cards.length; k++) {
      (function (id, bgClass) {
        var card = document.getElementById(id);
        if (!card) return;
        card.addEventListener("mouseenter", function () {
          for (var n = 0; n < allBg.length; n++) sliderBg.classList.remove(allBg[n]);
          sliderBg.classList.add(bgClass);
        });
      })(cards[k][0], cards[k][1]);
    }
  }
})();
