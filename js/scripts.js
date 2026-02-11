// js/scripts.js

// Máscaras para campos
function aplicarMascaraMoeda(input) {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = (value / 100).toFixed(2) + '';
        value = value.replace(".", ",");
        value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
        value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
        e.target.value = 'R$ ' + value;
    });
}

// Validação de formulários
function validarFormularioVenda(form) {
    const valor = parseFloat(form.valor.value.replace(/[^\d,]/g, '').replace(',', '.'));
    const valorPago = parseFloat(form.valor_pago.value.replace(/[^\d,]/g, '').replace(',', '.'));
    
    if (valor <= 0) {
        alert('Valor deve ser maior que zero');
        return false;
    }
    
    if (valorPago > 0 && valorPago < valor) {
        alert('Valor pago não pode ser menor que o valor da venda');
        return false;
    }
    
    return true;
}

// Auto-calculo de troco
function calcularTrocoAutomatico() {
    const valorInput = document.querySelector('input[name="valor"]');
    const valorPagoInput = document.querySelector('input[name="valor_pago"]');
    
    if (valorInput && valorPagoInput) {
        valorPagoInput.addEventListener('input', function() {
            const valor = parseFloat(valorInput.value.replace(/[^\d,]/g, '').replace(',', '.'));
            const valorPago = parseFloat(this.value.replace(/[^\d,]/g, '').replace(',', '.'));
            
            if (!isNaN(valor) && !isNaN(valorPago) && valorPago > valor) {
                const troco = valorPago - valor;
                // Pode exibir o troco em algum lugar da interface
                console.log('Troco: R$', troco.toFixed(2));
            }
        });
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar máscaras
    const inputsMoeda = document.querySelectorAll('input[type="text"][name*="valor"]');
    inputsMoeda.forEach(aplicarMascaraMoeda);
    
    // Calcular troco automático
    calcularTrocoAutomatico();
    
    // Animações de entrada
    const elements = document.querySelectorAll('.fade-in');
    elements.forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
    });
});

// Notificações
function mostrarNotificacao(mensagem, tipo = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform transition-transform duration-300 ${
        tipo === 'success' ? 'bg-green-500' : 
        tipo === 'error' ? 'bg-red-500' : 'bg-blue-500'
    } text-white`;
    notification.textContent = mensagem;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
