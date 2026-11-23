// O PHP continua validando tudo mesmo se o JavaScript estiver desativado.
const formulario = document.querySelector('#form-inscricao');
const mensagem = document.querySelector('#mensagem');
const contador = document.querySelector('#contador');
const grupo = document.querySelector('#grupo-oficinas');
const erro = document.querySelector('#oficinas-erro');
const oficinas = formulario.querySelectorAll('input[name="oficinas[]"]');

// Contagem consistente com o maxlength do HTML.
function atualizarContador() {
    const restantes = Math.max(0, mensagem.maxLength - mensagem.value.length);
    contador.textContent = restantes === 1 ? 'Resta 1 caractere' : `Restam ${restantes} caracteres`;
}
mensagem.addEventListener('input', atualizarContador);
window.addEventListener('pageshow', atualizarContador);
atualizarContador();

function oficinaSelecionada() {
    return Array.from(oficinas).some(oficina => oficina.checked);
}
function mostrarErro(mostrar) {
    grupo.classList.toggle('is-invalid', mostrar);
    erro.textContent = mostrar ? 'Selecione pelo menos uma oficina para continuar.' : '';
    for (const oficina of oficinas) {
        if (mostrar) oficina.setAttribute('aria-invalid', 'true');
        else oficina.removeAttribute('aria-invalid');
    }
}
formulario.addEventListener('submit', function (event) {
    if (!oficinaSelecionada()) {
        event.preventDefault();
        mostrarErro(true);
        oficinas[0].focus();
    } else {
        mostrarErro(false);
    }
});
for (const oficina of oficinas) {
    oficina.addEventListener('change', function () {
        if (oficinaSelecionada()) mostrarErro(false);
    });
}
