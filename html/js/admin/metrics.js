document.addEventListener('DOMContentLoaded', () => {
  const refreshButton = document.getElementById('refresh-metrics-btn');
  if (refreshButton) {
    refreshButton.addEventListener('click', () => {
      window.location.reload();
    });
  }
});
