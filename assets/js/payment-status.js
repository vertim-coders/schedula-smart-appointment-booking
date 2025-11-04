function setSchedulaPaymentStatus(status) {
  if (status === 'success' || status === 'cancelled') {
    localStorage.setItem('schesab_payment_status', status)
  }

  // A small delay to ensure localStorage is set before the window attempts to close.
  setTimeout(function () {
    window.close()

    // As a fallback if the window doesn't close, show a message.
    const messageDiv = document.getElementById('schedula-payment-message')
    if (messageDiv) {
      messageDiv.style.display = 'block'
    }
  }, 500)
}
