(function () {
  const wizard = document.querySelector('[data-wizard]');
  if (!wizard) return;

  const panes = [...wizard.querySelectorAll('.wizard-pane')];
  const bars = [...wizard.querySelectorAll('.step')];
  const currentInput = wizard.querySelector('input[name="current_step"]');
  let current = Number(currentInput?.value || 1);

  function draw() {
    panes.forEach((p, i) => p.classList.toggle('active', i + 1 === current));
    bars.forEach((b, i) => {
      b.classList.toggle('active', i + 1 === current);
      b.classList.toggle('done', i + 1 < current);
    });
    if (currentInput) currentInput.value = String(current);
  }

  function req(selector, msg) {
    const el = wizard.querySelector(selector);
    if (!el || String(el.value).trim() !== '') return true;
    Swal.fire({ icon: 'warning', text: msg, confirmButtonColor: '#DA291C' });
    el?.focus();
    return false;
  }

  wizard.addEventListener('click', (ev) => {
    const t = ev.target;
    if (!(t instanceof HTMLElement)) return;

    if (t.matches('[data-next]')) {
      if (current === 2) {
        if (!req('input[name="nome"]', 'Informe seu nome.')) return;
        if (!req('input[name="cpf"]', 'Informe seu CPF.')) return;
      }
      if (current === 3) {
        if (!req('input[name="telefone"]', 'Informe seu telefone.')) return;
        if (!req('input[name="empresa"]', 'Informe sua empresa.')) return;
        if (!req('input[name="setor"]', 'Informe seu setor.')) return;
      }
      if (current < panes.length) current++;
      draw();
    }

    if (t.matches('[data-prev]')) {
      if (current > 1) current--;
      draw();
    }

    const card = t.closest('.candidate-card');
    if (card) {
      wizard.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      const radio = card.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
    }
  });

  draw();
})();

function saveNumberImage(number) {
  const canvas = document.getElementById('drawNumberCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const now = new Date().toLocaleString('pt-BR');

  ctx.fillStyle = '#fff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = '#111827';
  ctx.font = 'bold 58px Arial';
  ctx.textAlign = 'center';
  ctx.fillText('CIPA Friato', canvas.width / 2, 120);

  ctx.fillStyle = '#DA291C';
  ctx.font = 'bold 150px Arial';
  ctx.fillText(number, canvas.width / 2, 330);

  ctx.fillStyle = '#374151';
  ctx.font = '28px Arial';
  ctx.fillText(now, canvas.width / 2, 430);

  const a = document.createElement('a');
  a.href = canvas.toDataURL('image/png');
  a.download = `numero-cipa-${number}.png`;
  a.click();
}
