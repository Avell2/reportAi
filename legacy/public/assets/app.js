document.getElementById('print')?.addEventListener('click', () => window.print());
// Abra a evidência quando um link interno apontar para um detalhe fechado.
window.addEventListener('hashchange', () => {
  const target = document.getElementById(location.hash.slice(1));
  if (target?.tagName === 'DETAILS') target.open = true;
});
