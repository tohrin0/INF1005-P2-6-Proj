document.addEventListener("DOMContentLoaded", function () {
  const bookingForm = document.getElementById("booking-form");

  if (!bookingForm || window.disableBookingFormAjax === true) {
    console.log("Booking form AJAX submission disabled");
    return; // Do nothing since we want standard form submission.
  }

  bookingForm.addEventListener("submit", function (event) {
    event.preventDefault();
    console.log("Form submitted via AJAX");
    if (validateForm()) {
      submitBooking(new FormData(bookingForm));
    }
  });

  function validateForm() {
    let isValid = true;
    const inputs = bookingForm.querySelectorAll(
      "input[required], select[required]"
    );
    inputs.forEach((input) => {
      if (!input.value.trim()) {
        isValid = false;
        input.classList.add("error");
      } else {
        input.classList.remove("error");
      }
    });
    return isValid;
  }

  function submitBooking(formData) {
    // Get the absolute path to booking.php
    const formAction = bookingForm.getAttribute("action") || window.location.pathname;
    
    console.log("Submitting to:", formAction);
    
    // Debug the form data being sent
    for (let pair of formData.entries()) {
      console.log(pair[0] + ': ' + pair[1]);
    }

    fetch(formAction, {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    })
    .then((response) => {
      console.log("Response status:", response.status);
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      
      // Check content type to handle both JSON and non-JSON responses
      const contentType = response.headers.get("content-type");
      if (contentType && contentType.includes("application/json")) {
        return response.json();
      } else {
        // If not JSON, get the text and log it for debugging
        return response.text().then(text => {
          console.error("Server returned non-JSON response:", text);
          throw new Error("Server returned non-JSON response. See console for details.");
        });
      }
    })
    .then((data) => {
      if (data.success) {
        window.location.href = data.redirect;
      } else {
        alert(data.message || "An error occurred during booking.");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("There was an error processing your booking. Please try again.");
    });
  }
});
