const form = document.getElementById('generate-form');
form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const button = form.querySelector('button');
  const input = form.querySelector('[name="symbol"]');
  const progress = document.getElementById('progress');
  const error = document.getElementById('error');
  const payload = new FormData(form);
  button.disabled = true;
  input.readOnly = true;
  progress.hidden = false;
  error.hidden = true;
  form.setAttribute('aria-busy', 'true');
  try {
    const response = await fetch(form.action, {method: 'POST', body: payload});
    const data = await response.json();
    if (!response.ok || !data.url) throw new Error(data.error || 'Não foi possível gerar o relatório. Tente novamente.');
    window.location.assign(data.url);
  } catch (failure) {
    error.textContent = failure.message || 'Conexão interrompida. Tente novamente.';
    error.hidden = false;
    progress.hidden = true;
    button.disabled = false;
    input.readOnly = false;
    form.removeAttribute('aria-busy');
  }
});
document.getElementById('print')?.addEventListener('click', () => window.print());

// Conferência dos números: executado somente ao abrir um relatório.
const apiReturnsElement = document.getElementById('api-returns');
if (apiReturnsElement) {
  const apiReturns = JSON.parse(apiReturnsElement.textContent);
  console.log('API returns — ' + apiReturns.symbol, apiReturns.responses);
}
