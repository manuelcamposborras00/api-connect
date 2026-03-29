<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Connect</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: system-ui, sans-serif;
            background: #f5f5f5;
            color: #222;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2rem 1rem;
        }

        main {
            width: 100%;
            max-width: 680px;
        }

        h1 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #111;
        }

        textarea {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            border: 1px solid #ccc;
            border-radius: 6px;
            resize: vertical;
            min-height: 120px;
            background: #fff;
        }

        textarea:focus {
            outline: none;
            border-color: #555;
        }

        button {
            margin-top: 0.75rem;
            padding: 0.6rem 1.4rem;
            font-size: 1rem;
            font-family: inherit;
            background: #222;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:disabled {
            background: #888;
            cursor: not-allowed;
        }

        #status {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #666;
            min-height: 1.2em;
        }

        #response-box {
            margin-top: 1.5rem;
            display: none;
        }

        #response-box h2 {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #888;
            margin-bottom: 0.5rem;
        }

        #response-text {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 1rem;
            font-size: 0.95rem;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        #response-text.error {
            border-color: #f5a5a5;
            background: #fff5f5;
            color: #c00;
        }

        #response-text.blocked {
            border-color: #f5c842;
            background: #fffbea;
            color: #7a5700;
        }
    </style>
</head>
<body>
<main>
    <h1>API Connect</h1>

    <form id="chat-form">
        <textarea
            id="prompt"
            placeholder="Type your message here..."
            maxlength="4000"
            required
        ></textarea>
        <br>
        <button type="submit" id="submit-btn">Send</button>
        <p id="status"></p>
    </form>

    <div id="response-box">
        <h2>Response</h2>
        <div id="response-text"></div>
    </div>
</main>

<script>
    const form        = document.getElementById('chat-form');
    const promptEl    = document.getElementById('prompt');
    const submitBtn   = document.getElementById('submit-btn');
    const statusEl    = document.getElementById('status');
    const responseBox = document.getElementById('response-box');
    const responseEl  = document.getElementById('response-text');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const prompt = promptEl.value.trim();
        if (!prompt) return;

        // Estado: enviando
        submitBtn.disabled = true;
        statusEl.textContent = 'Sending…';
        responseBox.style.display = 'none';
        responseEl.className = '';
        responseEl.textContent = '';

        try {
            const res = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt })
            });

            const data = await res.json();

            responseBox.style.display = 'block';

            if (data.error) {
                const isBlocked = data.error.toLowerCase().includes('blocked');
                responseEl.className = isBlocked ? 'blocked' : 'error';
                responseEl.textContent = data.error;
            } else {
                responseEl.textContent = data.response;
            }

            statusEl.textContent = '';
        } catch {
            responseBox.style.display = 'block';
            responseEl.className = 'error';
            responseEl.textContent = 'Network error. Please check your connection and try again.';
            statusEl.textContent = '';
        } finally {
            submitBtn.disabled = false;
        }
    });
</script>
</body>
</html>
