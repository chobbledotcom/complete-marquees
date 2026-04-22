(() => {
  const mobileNav = document.getElementById("mobile__nav");
  if (mobileNav) {
    const trigger = document.querySelector(".mobile__nav__trigger");
    const closer = document.querySelector(".mobile__nav__close");
    trigger?.addEventListener("click", () => mobileNav.classList.add("is-open"));
    closer?.addEventListener("click", () => mobileNav.classList.remove("is-open"));
  }

  const sliderBg = document.querySelector(".slider-bg");
  if (sliderBg) {
    const cards = {
      wedding__marquees: "wedding-bg",
      party__marquees: "party-bg",
      event__marquees: "event-bg",
      special__marquees: "special-bg",
    };
    for (const [id, bg] of Object.entries(cards)) {
      document.getElementById(id)?.addEventListener("mouseenter", () => {
        sliderBg.className = `slider-bg ${bg}`;
      });
    }
  }
})();
