// Speech Recognition
let recognition;
let isListening = false;

export function initSpeechRecognition() {
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;

        recognition.onresult = function (event) {
            const transcript = Array.from(event.results)
                .map(result => result[0])
                .map(result => result.transcript)
                .join('');

            document.getElementById('userInput').value = transcript;
        };

        recognition.onerror = function (event) {
            console.error('Speech recognition error', event.error);
            toggleSpeechRecognition();
        };

        recognition.onend = function () {
            if (isListening) {
                recognition.start();
            }
        };

        return true;
    } else {
        console.warn('Speech recognition not supported in this browser');
        return false;
    }
}

export function toggleSpeechRecognition() {
    if (!recognition) {
        if (!initSpeechRecognition()) {
            alert('Speech recognition is not supported in your browser');
            return;
        }
    }

    if (isListening) {
        recognition.stop();
        isListening = false;
        document.getElementById('speechButton').textContent = 'Start Listening';
    } else {
        recognition.start();
        isListening = true;
        document.getElementById('speechButton').textContent = 'Stop Listening';
    }
}

// Text to Speech
export function speakText(text) {
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        speechSynthesis.speak(utterance);
    } else {
        console.warn('Text-to-speech is not supported in this browser');
    }
}
