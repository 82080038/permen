# jQuery — Referensi & Patterns

Aplikasi ini menggunakan **jQuery 3.x** (bisa ditambahkan via CDN) untuk manipulasi DOM dan AJAX. Dokumen ini adalah acuan pengembangan.

---

## 1. Setup & CDN

```html
<!-- Bootstrap 5 + jQuery (compat mode) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Atau simpan lokal -->
<script src="assets/js/jquery-3.7.1.min.js"></script>
```

**Catatan**: Bootstrap 5 tidak membutuhkan jQuery untuk komponennya, tapi kita tetap gunakan jQuery untuk AJAX dan manipulasi DOM custom.

---

## 2. Selector Dasar

```javascript
// By ID
$('#timer')

// By class
$('.card-body')

// By tag
$('div')

// Descendant
$('#soalContainer .options')

// Multiple
$('#btnPrev, #btnNext')

// Attribute
$('[name="jawaban"]')

// First / last
$('.number-grid button').first()
$('.number-grid button').last()

// Filter
$('.number-grid button').filter('.answered')
```

---

## 3. Manipulasi DOM

```javascript
// HTML content
$('#soalContainer').html('<p>Soal baru</p>');

// Text content
$('#timer').text('25:00');

// Append / Prepend
$('.options').append('<label>Opsi baru</label>');

// Add / remove / toggle class
$('#btnNext').addClass('disabled');
$('#btnNext').removeClass('disabled');
$('#btnNext').toggleClass('active');

// Show / hide
$('#pembahasanBox').show();
$('#pembahasanBox').hide();
$('#pembahasanBox').toggle();

// CSS
$('#timer').css('background-color', '#e74c3c');

// Attribute
$('input[name="jawaban"]').attr('value', 'B');
$('input[type="radio"]').prop('checked', true);
```

---

## 4. Event Handling

```javascript
// Click
$('#btnNext').on('click', function() {
    nextSoal();
});

// Change (radio, select, checkbox)
$('input[name="jawaban"]').on('change', function() {
    const val = $(this).val();
    pilihJawaban(answerId, val, this);
});

// Submit form
$('#formLogin').on('submit', function(e) {
    e.preventDefault(); // hindari page reload
    const data = $(this).serialize(); // url-encoded string
    loginUser(data);
});

// Delegated event (untuk elemen dinamis)
$(document).on('click', '.number-grid button', function() {
    const idx = $(this).data('index');
    renderSoal(idx);
});
```

---

## 5. AJAX (Paling Penting untuk Aplikasi Ini)

### GET
```javascript
$.ajax({
    url: '../api/get_soal.php',
    method: 'GET',
    data: { session_id: 123 },
    dataType: 'json',
    success: function(response) {
        console.log(response.soal);
    },
    error: function(xhr, status, error) {
        console.error('Error:', error);
    }
});

// Shorthand
$.getJSON('../api/get_soal.php', { session_id: 123 }, function(data) {
    console.log(data);
});
```

### POST JSON
```javascript
$.ajax({
    url: '../api/submit_jawaban.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({ answer_id: 5, jawaban: 'B' }),
    dataType: 'json',
    success: function(response) {
        if (response.success) {
            console.log('Skor:', response.skor);
        }
    },
    error: function(xhr) {
        console.error('HTTP ' + xhr.status + ': ' + xhr.responseText);
    }
});
```

### POST Form Data
```javascript
$.ajax({
    url: '../api/login.php',
    method: 'POST',
    data: {
        email: $('#email').val(),
        password: $('#password').val()
    },
    dataType: 'json',
    success: function(response) {
        if (response.success) {
            window.location.href = 'dashboard.php';
        } else {
            alert(response.error);
        }
    }
});
```

### Async/Await Pattern (Modern)
```javascript
async function loadSoal(sessionId) {
    try {
        const response = await $.ajax({
            url: '../api/get_soal.php',
            method: 'GET',
            data: { session_id: sessionId },
            dataType: 'json'
        });
        return response.soal;
    } catch (error) {
        console.error('Gagal memuat soal:', error);
        return [];
    }
}
```

---

## 6. Traversal

```javascript
// Parent
$(el).parent()

// Closest ancestor
$(el).closest('label')

// Siblings
$(el).siblings().removeClass('selected');

// Find descendants
$('.card').find('.card-body')

// Next / prev
$(el).next()
$(el).prev()
```

---

## 7. Utility Functions

```javascript
// Each
$('.options label').each(function(index, element) {
    $(this).addClass('opt-' + index);
});

// Map
const values = $('input[name="jawaban"]').map(function() {
    return $(this).val();
}).get(); // -> ['A','B','C','D','E']

// Is checked
if ($('#checkbox').is(':checked')) { ... }

// Data attributes
$('.number-grid button').data('index', i); // set
const idx = $(this).data('index');         // get
```

---

## 8. Animasi

```javascript
// Fade
$('#box').fadeIn(300);
$('#box').fadeOut(300);

// Slide
$('#panel').slideDown(300);
$('#panel').slideUp(300);

// Custom
$('#card').animate({ opacity: 0.5, marginLeft: '20px' }, 300);
```

---

## 9. Best Practices untuk Aplikasi Ini

### a. Gunakan $(document).ready()
```javascript
$(document).ready(function() {
    // Kode yang bergantung pada DOM siap
    loadSoal();
});

// Shorthand
$(function() {
    loadSoal();
});
```

### b. Hindari Memory Leak
```javascript
// Hapus event listener saat elemen dihapus
$('#oldElement').off('click').remove();
```

### c. Cache Selector
```javascript
// ❌ Buruk — query DOM berulang kali
$('#timer').text('...');
$('#timer').css('color', 'red');

// ✅ Baik — cache selector
const $timer = $('#timer');
$timer.text('...');
$timer.css('color', 'red');
```

### d. Gunakan data-* untuk state
```javascript
// Simpan state di elemen, bukan variabel global berantakan
$('#soalCard').data('current-index', 0);
$('#soalCard').data('session-id', 123);
```

---

## 10. Pola Umum dalam Aplikasi Ini

### Render soal dinamis
```javascript
function renderSoal(soalData) {
    let html = '<div class="question">' + escapeHtml(soalData.pertanyaan) + '</div>';
    html += '<div class="options">';
    ['A','B','C','D','E'].forEach(opt => {
        html += '<label><input type="radio" name="jawaban" value="' + opt + '"> ' + opt + '</label>';
    });
    html += '</div>';
    $('#soalContainer').html(html);
}
```

### Countdown Timer
```javascript
let totalSeconds = 80 * 60;
const timerInterval = setInterval(() => {
    totalSeconds--;
    const m = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
    const s = (totalSeconds % 60).toString().padStart(2, '0');
    $('#timer').text(m + ':' + s);
    if (totalSeconds <= 0) {
        clearInterval(timerInterval);
        finishTryout();
    }
}, 1000);
```

---

## 11. Referensi

- [jQuery API Documentation](https://api.jquery.com/)
- [Learn jQuery](https://learn.jquery.com/)
