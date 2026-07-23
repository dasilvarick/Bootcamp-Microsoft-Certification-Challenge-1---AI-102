// Lógica de Tabs
function openTab(tabName) {
    const contents = document.querySelectorAll('.tab-content');
    const buttons = document.querySelectorAll('.tab-btn');

    contents.forEach(content => content.classList.remove('active'));
    buttons.forEach(btn => btn.classList.remove('active'));

    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Configuração da URL base chamando o arquivo api.php
const API_BASE_URL = 'api.php';

// Envio de Manifestação
document.getElementById('manifestationForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;

    // Helper to get checked radio value
    const getRadioValue = (name) => {
        const ele = document.querySelector(`input[name="${name}"]:checked`);
        return ele ? ele.value : '';
    };

    const payload = {
        tipo_pessoa: getRadioValue('tipo_pessoa'),
        publicacao: getRadioValue('publicacao'),
        setor: document.getElementById('setor').value,
        name: document.getElementById('name').value,
        matricula: document.getElementById('matricula').value,
        endereco: document.getElementById('endereco').value,
        bairro: document.getElementById('bairro').value,
        cidade: document.getElementById('cidade').value,
        cep: document.getElementById('cep').value,
        email: document.getElementById('email').value,
        telefone: document.getElementById('telefone').value,
        celular: document.getElementById('celular').value,
        email_secundario: document.getElementById('email_secundario').value,
        sigilo: getRadioValue('sigilo'),
        assunto: document.getElementById('assunto').value,
        message: document.getElementById('message').value
    };

    const resultDiv = document.getElementById('formResult');
    const submitBtn = form.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando...';
    resultDiv.className = 'result-message'; // reset

    try {
        const response = await fetch(`${API_BASE_URL}?action=create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok) {
            resultDiv.classList.add('success');
            resultDiv.innerHTML = `<strong>Sucesso!</strong> Sua manifestação foi registrada.<br>Guarde seu protocolo: <strong>${data.protocol}</strong>`;
            document.getElementById('manifestationForm').reset();
        } else {
            resultDiv.classList.add('error');
            resultDiv.innerText = data.error || 'Erro ao enviar a manifestação.';
        }
    } catch (error) {
        resultDiv.classList.add('error');
        resultDiv.innerText = 'Erro de conexão com o servidor.';
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Enviar Manifestação';
    }
});

// Consulta de Protocolo
document.getElementById('searchForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const protocol = document.getElementById('protocolInput').value.trim();
    const resultBox = document.getElementById('searchResult');
    const submitBtn = e.target.querySelector('button');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Buscando...';
    resultBox.classList.add('hidden');

    try {
        const response = await fetch(`${API_BASE_URL}?action=status&protocol=${encodeURIComponent(protocol)}`);
        const data = await response.json();

        if (response.ok) {
            // Safari cross-browser date support (replace space with T)
            const safeDateStr = data.createdAt.replace(' ', 'T');
            const date = new Date(safeDateStr).toLocaleDateString('pt-BR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });

            // Prevent XSS robustly by creating DOM elements directly
            resultBox.innerHTML = '';

            const protocolP = document.createElement('p');
            protocolP.innerHTML = `<strong>Protocolo:</strong> ${document.createTextNode(data.protocol).textContent}`;

            const nameP = document.createElement('p');
            nameP.innerHTML = `<strong>Nome:</strong> ${document.createTextNode(data.name).textContent}`;

            const dateP = document.createElement('p');
            dateP.innerHTML = `<strong>Data:</strong> ${date}`;

            const statusP = document.createElement('p');
            statusP.innerHTML = `<strong>Status:</strong> <span class="status-badge">${document.createTextNode(data.status).textContent}</span>`;

            resultBox.appendChild(protocolP);
            resultBox.appendChild(nameP);
            resultBox.appendChild(dateP);
            resultBox.appendChild(statusP);

            resultBox.classList.remove('hidden');
        } else {
            resultBox.innerHTML = `<p style="color: red;">${data.error || 'Protocolo não encontrado.'}</p>`;
            resultBox.classList.remove('hidden');
        }
    } catch (error) {
        resultBox.innerHTML = `<p style="color: red;">Erro de conexão com o servidor.</p>`;
        resultBox.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Consultar';
    }
});
