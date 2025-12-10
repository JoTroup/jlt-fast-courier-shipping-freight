"use strict";
var $ = jQuery.noConflict();
function toggleLoader() {
  $(".loader").toggleClass("active");
}

function validateFormsInputs(form) {
  const formFields = $(form);
  let i = 0;
  let isValid = true;

  while (i < formFields[0].length) {
    const field = $(formFields[0][i]);

    if (field.attr("required") && !field.val()) {
      const idOfField = field.attr("id");
      if (!$(`[error-for=${idOfField}]`).length) {
        $(`[for=${idOfField}]`).append(
          `<small class='text-danger label-error' error-for=${idOfField}>(Required)</small>`
        );
        field.addClass("border-danger");
      }

      isValid = false;
    }
    i++;
  }

  return isValid;
}

function handleFormInputChange() {
  const field = $("input:focus");
  const idOfField = field.attr("id");

  if ($(`[error-for=${idOfField}]`).length) {
    $(`[error-for=${idOfField}]`).remove();
    field.removeClass("border-danger");
  }
}

function isValidEmail(email) {
  var validRegex =
    /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;

  if (email.match(validRegex)) {
    return true;
  } else {
    Swal.fire("", "Invalid Email", "error");
    return false;
  }
}

// removed the local storage on logout
$(document).on("click", ".merchant-logout", function () {
  localStorage.removeItem("selectedTabHref");
});
