const fadeElements = document.querySelectorAll(".fade-in");
const openButtons = document.querySelectorAll("[data-open-form]");
const closeButtons = document.querySelectorAll("[data-close-form]");
const modal = document.getElementById("consultation-form");
const form = document.querySelector(".premium-form");
const formSuccess = document.querySelector(".form-success");
const formError = document.getElementById("form-error");
const submitButton = document.getElementById("submit-btn");

if (fadeElements.length > 0) {
  const observer = new IntersectionObserver(
    (entries, obs) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          obs.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.16,
      rootMargin: "0px 0px -30px 0px",
    }
  );

  fadeElements.forEach((el) => observer.observe(el));
}

function openModal() {
  if (!modal) return;
  modal.classList.add("is-open");
  modal.setAttribute("aria-hidden", "false");
  document.body.classList.add("modal-open");
}

function closeModal() {
  if (!modal) return;
  modal.classList.remove("is-open");
  modal.setAttribute("aria-hidden", "true");
  document.body.classList.remove("modal-open");
}

openButtons.forEach((button) => {
  button.addEventListener("click", openModal);
});

closeButtons.forEach((button) => {
  button.addEventListener("click", closeModal);
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    closeModal();
  }
});

if (form) {
  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (formError) {
      formError.hidden = true;
    }

    const defaultButtonLabel = submitButton ? submitButton.textContent : "";

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = "Wysyłanie...";
    }

    try {
      const formData = new FormData(form);
      const response = await fetch("./submit.php", {
        method: "POST",
        body: formData,
        headers: {
          Accept: "application/json",
        },
      });

      const result = await response.json();

      if (!response.ok || !result.ok) {
        throw new Error(result.message || "Błąd wysyłki");
      }

      form.hidden = true;
      if (formSuccess) {
        formSuccess.hidden = false;
      }
    } catch (_error) {
      if (formError) {
        formError.hidden = false;
      }
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = defaultButtonLabel;
      }
    }
  });
}

if (modal) {
  modal.addEventListener("transitionend", () => {
    if (!modal.classList.contains("is-open") && form) {
      form.hidden = false;
      if (formSuccess) {
        formSuccess.hidden = true;
      }
      if (formError) {
        formError.hidden = true;
      }
      form.reset();
    }
  });
}
