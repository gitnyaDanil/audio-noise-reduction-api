<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Audio Noise Reduction — Upload your audio or video file and let AI remove background noise instantly.">
    <title>Audio Noise Reduction</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0d0f14;
            --surface:   #13161e;
            --card:      #1a1d27;
            --border:    #252836;
            --border-hi: #3a3f5c;
            --accent:    #6c63ff;
            --accent-2:  #a78bfa;
            --green:     #22d3a5;
            --red:       #f87171;
            --text:      #e2e8f0;
            --muted:     #64748b;
            --radius:    16px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
        }

        /* Ambient blobs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.12;
            pointer-events: none;
            z-index: 0;
        }
        body::before {
            width: 500px; height: 500px;
            background: var(--accent);
            top: -150px; left: -150px;
        }
        body::after {
            width: 400px; height: 400px;
            background: var(--accent-2);
            bottom: -100px; right: -100px;
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 560px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 36px;
        }
        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .logo-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 24px rgba(108,99,255,.4);
        }
        .logo-icon svg { width: 22px; height: 22px; fill: white; }
        .logo-text { font-size: 1.2rem; font-weight: 700; letter-spacing: -0.02em; }
        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, var(--text) 0%, var(--muted) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
            margin-bottom: 10px;
        }
        .header p {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Card */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: 0 24px 64px rgba(0,0,0,.4);
        }

        /* Drop zone */
        #drop-zone {
            border: 2px dashed var(--border-hi);
            border-radius: 12px;
            padding: 48px 24px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s, transform .15s;
            position: relative;
            overflow: hidden;
        }
        #drop-zone:hover, #drop-zone.drag-over {
            border-color: var(--accent);
            background: rgba(108,99,255,.06);
        }
        #drop-zone.drag-over { transform: scale(1.01); }
        #drop-zone input[type="file"] {
            position: absolute; inset: 0;
            opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }

        .drop-icon {
            width: 56px; height: 56px;
            margin: 0 auto 16px;
            border-radius: 14px;
            background: rgba(108,99,255,.12);
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
        }
        #drop-zone:hover .drop-icon, #drop-zone.drag-over .drop-icon {
            background: rgba(108,99,255,.22);
        }
        .drop-icon svg { width: 26px; height: 26px; }

        .drop-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .drop-sub {
            font-size: 0.8rem;
            color: var(--muted);
        }
        .drop-sub span {
            color: var(--accent-2);
            font-weight: 500;
        }

        .accepted-formats {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .format-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 100px;
            background: rgba(255,255,255,.06);
            color: var(--muted);
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        /* File preview */
        #file-preview {
            display: none;
            align-items: center;
            gap: 14px;
            background: rgba(108,99,255,.08);
            border: 1px solid rgba(108,99,255,.3);
            border-radius: 10px;
            padding: 14px;
            margin-top: 16px;
        }
        #file-preview.visible { display: flex; }
        .file-thumb {
            width: 42px; height: 42px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .file-thumb svg { width: 20px; height: 20px; }
        .file-info { flex: 1; min-width: 0; }
        .file-name {
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .file-size {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 2px;
        }
        .file-remove {
            background: none; border: none;
            color: var(--muted); cursor: pointer;
            padding: 4px; border-radius: 6px;
            transition: color .2s, background .2s;
            display: flex;
        }
        .file-remove:hover { color: var(--red); background: rgba(248,113,113,.1); }

        /* Submit button */
        #submit-btn {
            margin-top: 20px;
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: white;
            letter-spacing: .01em;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 8px 24px rgba(108,99,255,.35);
        }
        #submit-btn:hover:not(:disabled) {
            opacity: .9;
            transform: translateY(-1px);
            box-shadow: 0 12px 32px rgba(108,99,255,.45);
        }
        #submit-btn:active:not(:disabled) { transform: translateY(0); }
        #submit-btn:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; transform: none; }

        /* Progress area */
        #progress-area { display: none; margin-top: 20px; }
        #progress-area.visible { display: block; }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .progress-label { font-size: 0.82rem; font-weight: 600; color: var(--text); }
        .progress-pct { font-size: 0.82rem; color: var(--accent-2); font-weight: 700; }

        .progress-bar-wrap {
            height: 6px;
            background: var(--border);
            border-radius: 100px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 100px;
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            transition: width .5s ease;
            width: 0%;
        }

        .status-msg {
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 8px;
            min-height: 18px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .pulse-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--accent-2);
            animation: pulse 1.5s infinite;
            flex-shrink: 0;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .3; transform: scale(.7); }
        }

        /* Result */
        #result-area { display: none; margin-top: 20px; }
        #result-area.visible { display: block; }

        .result-card {
            border-radius: 12px;
            padding: 18px;
            border: 1px solid;
        }
        .result-card.success {
            background: rgba(34,211,165,.07);
            border-color: rgba(34,211,165,.3);
        }
        .result-card.error {
            background: rgba(248,113,113,.07);
            border-color: rgba(248,113,113,.3);
        }
        .result-head {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .result-icon {
            width: 34px; height: 34px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .result-icon.success { background: rgba(34,211,165,.15); color: var(--green); }
        .result-icon.error   { background: rgba(248,113,113,.15); color: var(--red); }
        .result-title { font-size: 0.92rem; font-weight: 700; }
        .result-sub   { font-size: 0.78rem; color: var(--muted); margin-top: 2px; }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            background: var(--green);
            color: #0d1612;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            transition: opacity .2s, transform .15s;
            box-shadow: 0 4px 16px rgba(34,211,165,.3);
        }
        .download-btn:hover { opacity: .9; transform: translateY(-1px); }

        .job-id-box {
            font-size: 0.72rem;
            color: var(--muted);
            background: rgba(255,255,255,.04);
            border-radius: 6px;
            padding: 6px 10px;
            margin-top: 10px;
            font-family: monospace;
            word-break: break-all;
        }

        /* Waveform animation */
        .waveform {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
            margin: 0 auto 14px;
        }
        .waveform .bar {
            width: 3px;
            border-radius: 3px;
            background: linear-gradient(to top, var(--accent), var(--accent-2));
            animation: wave 1.2s ease-in-out infinite;
        }
        .waveform .bar:nth-child(1) { height: 12px; animation-delay: 0s; }
        .waveform .bar:nth-child(2) { height: 20px; animation-delay: .15s; }
        .waveform .bar:nth-child(3) { height: 28px; animation-delay: .3s; }
        .waveform .bar:nth-child(4) { height: 18px; animation-delay: .45s; }
        .waveform .bar:nth-child(5) { height: 24px; animation-delay: .6s; }
        .waveform .bar:nth-child(6) { height: 14px; animation-delay: .75s; }
        .waveform .bar:nth-child(7) { height: 22px; animation-delay: .9s; }

        @keyframes wave {
            0%, 100% { transform: scaleY(1); }
            50% { transform: scaleY(.3); }
        }

        /* Reset link */
        .reset-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: var(--muted);
            cursor: pointer;
            transition: color .2s;
        }
        .reset-link:hover { color: var(--accent-2); }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 28px;
            font-size: 0.75rem;
            color: var(--muted);
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Header -->
    <div class="header">
        <div class="logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 3a9 9 0 1 0 9 9A9 9 0 0 0 12 3zm0 16a7 7 0 1 1 7-7 7 7 0 0 1-7 7zm0-11a4 4 0 0 0-4 4h2a2 2 0 0 1 4 0h2a4 4 0 0 0-4-4zm-1 4h2v3h-2z"/>
                </svg>
            </div>
            <span class="logo-text">ClearVoice AI</span>
        </div>
        <h1>Remove Background<br>Noise from Audio</h1>
        <p>Drag & drop your file below. Our AI will strip out<br>unwanted noise and return a clean recording.</p>
    </div>

    <!-- Card -->
    <div class="card" id="main-card">

        <!-- Drop Zone -->
        <div id="drop-zone">
            <input type="file" id="file-input" accept=".mp4,.mov,.webm,.avi" />

            <div class="waveform">
                <div class="bar"></div><div class="bar"></div><div class="bar"></div>
                <div class="bar"></div><div class="bar"></div><div class="bar"></div>
                <div class="bar"></div>
            </div>

            <p class="drop-title">Drop your file here</p>
            <p class="drop-sub">or <span>click to browse</span> your computer</p>

            <div class="accepted-formats">
                <span class="format-badge">MP4</span>
                <span class="format-badge">MOV</span>
                <span class="format-badge">WEBM</span>
                <span class="format-badge">AVI</span>
                <span class="format-badge">Max 100MB</span>
            </div>
        </div>

        <!-- File preview -->
        <div id="file-preview">
            <div class="file-thumb">
                <svg viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 3v10.55A4 4 0 1 0 14 17V7h4V5h-4l-2-2z"/>
                </svg>
            </div>
            <div class="file-info">
                <div class="file-name" id="preview-name">—</div>
                <div class="file-size" id="preview-size">—</div>
            </div>
            <button class="file-remove" id="file-remove-btn" title="Remove file">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
                </svg>
            </button>
        </div>

        <!-- Submit -->
        <button id="submit-btn" disabled>
            Upload & Process
        </button>

        <!-- Progress -->
        <div id="progress-area">
            <div class="progress-header">
                <span class="progress-label" id="progress-label">Uploading…</span>
                <span class="progress-pct" id="progress-pct">0%</span>
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" id="progress-bar"></div>
            </div>
            <div class="status-msg">
                <span class="pulse-dot"></span>
                <span id="status-text">Sending file to server…</span>
            </div>
        </div>

        <!-- Result -->
        <div id="result-area"></div>

    </div>

    <span class="reset-link" id="reset-link" style="display:none">← Process another file</span>

    <div class="footer">
        Powered by DeepFilterNet AI · All uploads are processed securely
    </div>

</div>

<script>
    const dropZone     = document.getElementById('drop-zone');
    const fileInput    = document.getElementById('file-input');
    const filePreview  = document.getElementById('file-preview');
    const previewName  = document.getElementById('preview-name');
    const previewSize  = document.getElementById('preview-size');
    const removeBtn    = document.getElementById('file-remove-btn');
    const submitBtn    = document.getElementById('submit-btn');
    const progressArea = document.getElementById('progress-area');
    const progressBar  = document.getElementById('progress-bar');
    const progressPct  = document.getElementById('progress-pct');
    const progressLabel= document.getElementById('progress-label');
    const statusText   = document.getElementById('status-text');
    const resultArea   = document.getElementById('result-area');
    const resetLink    = document.getElementById('reset-link');

    let selectedFile = null;
    let pollInterval = null;

    /* ── Drag & Drop ── */
    ['dragenter','dragover'].forEach(evt => {
        dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    });
    ['dragleave','drop'].forEach(evt => {
        dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.remove('drag-over'); });
    });
    dropZone.addEventListener('drop', e => {
        const file = e.dataTransfer.files[0];
        if (file) handleFile(file);
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) handleFile(fileInput.files[0]);
    });

    function handleFile(file) {
        selectedFile = file;
        previewName.textContent = file.name;
        previewSize.textContent = formatBytes(file.size);
        filePreview.classList.add('visible');
        submitBtn.disabled = false;
    }

    removeBtn.addEventListener('click', () => {
        selectedFile = null;
        fileInput.value = '';
        filePreview.classList.remove('visible');
        submitBtn.disabled = true;
    });

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    /* ── Upload ── */
    submitBtn.addEventListener('click', () => {
        if (!selectedFile) return;
        startUpload();
    });

    function startUpload() {
        submitBtn.disabled = true;
        dropZone.style.pointerEvents = 'none';
        progressArea.classList.add('visible');
        resultArea.innerHTML = '';
        resultArea.classList.remove('visible');

        const formData = new FormData();
        formData.append('file', selectedFile);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/upload', true);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 50); // upload = 0–50%
                setProgress(pct, 'Uploading…', `Sent ${formatBytes(e.loaded)} of ${formatBytes(e.total)}`);
            }
        };

        xhr.onload = () => {
            if (xhr.status === 202) {
                const data = JSON.parse(xhr.responseText);
                setProgress(55, 'Processing…', 'File received. AI is working…');
                pollStatus(data.job_id, data.status_url);
            } else {
                let msg = 'Upload failed.';
                try {
                    const err = JSON.parse(xhr.responseText);
                    msg = err.message || (err.errors && Object.values(err.errors).flat()[0]) || msg;
                } catch(e) {}
                showError(msg);
            }
        };

        xhr.onerror = () => showError('Network error. Please try again.');
        xhr.send(formData);
    }

    function pollStatus(jobId, statusUrl) {
        let attempts = 0;
        const maxAttempts = 660; // 11 min; exceeds the 10-minute worker timeout

        pollInterval = setInterval(async () => {
            attempts++;
            if (attempts > maxAttempts) {
                clearInterval(pollInterval);
                showError('Processing timed out. Please try again.');
                return;
            }

            try {
                const res = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                const serverPct = data.progress ?? 0;
                const uiPct = 55 + Math.round(serverPct * 0.45); // map 0–100 → 55–100

                if (data.status === 'completed' || data.status === 'fallback') {
                    clearInterval(pollInterval);
                    setProgress(100, 'Done!', 'Processing complete.');
                    showSuccess(data, jobId);
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    showError(data.message || 'Processing failed.');
                } else {
                    setProgress(uiPct, 'Processing…', data.message || 'AI noise reduction in progress…');
                }
            } catch (err) {
                // ignore transient errors
            }
        }, 1000);
    }

    function setProgress(pct, label, text) {
        progressBar.style.width = pct + '%';
        progressPct.textContent = pct + '%';
        progressLabel.textContent = label;
        statusText.textContent = text;
    }

    function showSuccess(data, jobId) {
        progressArea.classList.remove('visible');
        resultArea.classList.add('visible');

        const isFallback = data.status === 'fallback';

        resultArea.innerHTML = `
            <div class="result-card success">
                <div class="result-head">
                    <div class="result-icon success">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" fill="none"
                                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <div class="result-title">${isFallback ? 'Processed (Fallback)' : 'Noise Reduction Complete!'}</div>
                        <div class="result-sub">${isFallback ? 'DeepFilterNet unavailable — original returned clean.' : 'Your audio has been cleaned successfully.'}</div>
                    </div>
                </div>
                <a class="download-btn" href="${data.download_url}" download target="_blank">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 16l-6-6h4V4h4v6h4l-6 6zm-6 4h12v-2H6v2z"/>
                    </svg>
                    Download Processed File
                </a>
                <div class="job-id-box">Job ID: ${jobId}</div>
            </div>`;

        resetLink.style.display = 'block';
    }

    function showError(message) {
        clearInterval(pollInterval);
        progressArea.classList.remove('visible');
        resultArea.classList.add('visible');
        submitBtn.disabled = false;
        dropZone.style.pointerEvents = '';

        resultArea.innerHTML = `
            <div class="result-card error">
                <div class="result-head">
                    <div class="result-icon error">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/>
                        </svg>
                    </div>
                    <div>
                        <div class="result-title">Something went wrong</div>
                        <div class="result-sub"></div>
                    </div>
                </div>
            </div>`;

        resultArea.querySelector('.result-sub').textContent = message;

        resetLink.style.display = 'block';
    }

    /* ── Reset ── */
    resetLink.addEventListener('click', () => {
        selectedFile = null;
        fileInput.value = '';
        filePreview.classList.remove('visible');
        submitBtn.disabled = true;
        progressArea.classList.remove('visible');
        resultArea.innerHTML = '';
        resultArea.classList.remove('visible');
        progressBar.style.width = '0%';
        progressPct.textContent = '0%';
        dropZone.style.pointerEvents = '';
        resetLink.style.display = 'none';
        clearInterval(pollInterval);
    });
</script>
</body>
</html>
