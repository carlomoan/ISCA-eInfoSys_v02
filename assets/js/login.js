$(document).ready(function () {
  $('#login-form').on('submit', function (e) {
    e.preventDefault();

    const $form = $(this);
    const email = $('#email').val().trim();
    const password = $('#password').val();
    const csrfToken = $('input[name="csrf_token"]').val();
    const $button = $form.find('button[type="submit"]');

    // Clear any previous messages
    $('.error-message').text('');
    $('#login-error-message').hide();

    let hasError = false;

    if (!validateEmail(email)) {
      $('#email-error').text('Please enter a valid email address.');
      hasError = true;
    }

    if (password.length < 6) {
      $('#password-error').text('Password must be at least 6 characters.');
      hasError = true;
    }

    if (hasError) return;

    $button.prop('disabled', true).html('<span class="spinner"></span> Verification...');

    $.ajax({
      url: BASE_URL + '/api/auth/login.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        email,
        password,
        csrf_token: csrfToken
      }),
      dataType: 'json',
      success: function (resp) {
        if (resp.success) {
          window.location.href = resp.redirect || BASE_URL + '/index.php?page=dashboard';
        } else {
          $('#login-error-message').text(resp.error || 'Login failed').show();
          $button.prop('disabled', false).text('Login');
        }
      },
      error: function (xhr, status, error) {
        console.error('Login error:', xhr.responseText);
        $('#login-error-message').text('An unexpected error occurred. Please try again.').show();
        $button.prop('disabled', false).text('Login');
      }
    });
  });

  // Password strength checker
  $('#password').on('input', function () {
    const pwd = $(this).val();
    let strength = 0;
    if (pwd.length >= 6) strength++;
    if (/[A-Z]/.test(pwd)) strength++;
    if (/[0-9]/.test(pwd)) strength++;
    if (/[\W]/.test(pwd)) strength++;

    const strengthText = ['Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['red', 'orange', 'blue', 'green'];

    $('#password-strength')
      .text('Password Strength: ' + (strengthText[strength - 1] || 'Too short'))
      .css('color', colors[strength - 1] || 'gray');
  });

  function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
  }
});
