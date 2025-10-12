$(document).ready(function () {
  // Login form validation & AJAX
  $('#login-form').on('submit', function (e) {
    e.preventDefault();

    const $form = $(this);
    const email = $('#email').val().trim();
    const password = $('#password').val();
    const csrfToken = $('input[name="csrf_token"]').val();
    const $button = $form.find('button[type="submit"]');

    // Clear previous error messages
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

    $button.prop('disabled', true).html('<span class="spinner"></span> Logging in...');

    $.ajax({
      url: BASE_URL + '/assets/js/ajax.php',
      method: 'POST',
      data: {
        email,
        password,
        csrf_token: csrfToken
      },
      dataType: 'json',
      success: function (resp) {
        if (resp.status === 'success') {
          window.location.href = BASE_URL + '/index.php?page=dashboard';
        } else {
          $('#login-error-message').text(resp.message).show();
          $button.prop('disabled', false).text('Login');
        }
      },
      error: function () {
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

// Sidebar toggle (mobile)
document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.getElementById('menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  toggleBtn?.addEventListener('click', () => sidebar.classList.toggle('active'));

  // Dark mode toggle
  const darkToggle = document.getElementById('dark-toggle');
  darkToggle?.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    localStorage.setItem('darkMode', document.body.classList.contains('dark') ? '1' : '0');
  });

  // Load dark mode setting from localStorage
  if (localStorage.getItem('darkMode') === '1') {
    document.body.classList.add('dark');
  }

  // Profile dropdown toggle
  const profileBtn = document.getElementById('profile-btn');
  const dropdownMenu = document.querySelector('.dropdown-menu');
  profileBtn?.addEventListener('click', () => dropdownMenu.classList.toggle('show'));

  // Close profile dropdown if clicked outside
  document.addEventListener('click', e => {
    if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
      dropdownMenu.classList.remove('show');
    }
  });
});
