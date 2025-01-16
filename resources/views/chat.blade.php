<!DOCTYPE html>
<html>
<head>
    <title>Chat AI</title>
</head>
<body>
    <h1>Chat con IA</h1>
    <form id="chatForm">
        <input type="text" id="message" placeholder="Escribe tu pregunta">
        <button type="submit">Enviar</button>
    </form>
    <div id="chatOutput"></div>

    <script>
        document.getElementById('chatForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const message = document.getElementById('message').value;
            const response = await fetch('/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ message })
            });
            const data = await response.json();
            document.getElementById('chatOutput').innerHTML += `<p><strong>Tú:</strong> ${message}</p><p><strong>IA:</strong> ${data.response}</p>`;
            document.getElementById('message').value = '';
        });
    </script>
</body>
</html>
