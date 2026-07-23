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

    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const message = document.getElementById('message').value;
    const resultDiv = document.getElementById('formResult');
    const submitBtn = e.target.querySelector('button');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando...';
    resultDiv.className = 'result-message'; // reset

    try {
        const response = await fetch(`${API_BASE_URL}?action=create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ name, email, message })
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
            const date = new Date(data.createdAt).toLocaleDateString('pt-BR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });

            // Prevent XSS
            const safeProtocol = document.createTextNode(data.protocol).textContent;
            const safeName = document.createTextNode(data.name).textContent;
            const safeStatus = document.createTextNode(data.status).textContent;

            resultBox.innerHTML = `
                <p><strong>Protocolo:</strong> ${safeProtocol}</p>
                <p><strong>Nome:</strong> ${safeName}</p>
                <p><strong>Data:</strong> ${date}</p>
                <p><strong>Status:</strong> <span class="status-badge">${safeStatus}</span></p>
            `;
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
