<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body class="preview">
    <div class="target">
        <div class="top" id="drop-area"></div>
    </div>

    <div class="images" id="images-container">
        <?php
        // Загрузка данных из image.json
        $jsonData = file_get_contents('image.json');
        $imagesData = json_decode($jsonData, true);

        // Подсчет количества изображений с correct: true
        $totalCorrectImages = 0;
        foreach ($imagesData as $imageData) {
            if ($imageData['correct']) {
                $totalCorrectImages++;
            }
        }

        // Вывод изображений
        foreach ($imagesData as $imageData) {
            ($imageData['correct'] ? 'true' : 'false');
        }
        ?>
    </div>

    <div class="final-message" id="final-message">Изображение успешно добавлено!</div>
    <div class="fighter-message" id="fighter-message">Боец собран!</div>
    <button class="button-reboot" id="button-reboot">Вернуться</button>
    <button class="button-start" id="button-start">Начать</button>

    <script>
        let totalImages = 0; // Будет обновлено после загрузки изображений
        let totalCorrectImages = <?php echo $totalCorrectImages; ?>; // Количество изображений с correct: true
        let imagesDropped = 0;
        let inactivityTime;

        // Функция сброса таймера
        function resetTimer() {
            clearTimeout(inactivityTime);
            inactivityTime = setTimeout(() => {
                location.reload();
            }, 90000);
        }

        document.addEventListener('mousemove', resetTimer);
        document.addEventListener('keypress', resetTimer);
        document.addEventListener('click', resetTimer);
        document.addEventListener('scroll', resetTimer);

        function drag(event) {
            event.dataTransfer.setData("text", event.target.src);
        }

        const dropArea = document.getElementById('drop-area');
        const finalMessage = document.getElementById('final-message');
        const fighterMessage = document.getElementById('fighter-message');
        const buttonReboot = document.getElementById('button-reboot');
        const buttonStart = document.getElementById('button-start');
        const imagesContainer = document.getElementById('images-container');
        const body = document.body;

        buttonStart.addEventListener('click', () => {
            body.classList.remove('preview'); // Убираем класс preview
            buttonStart.style.display = 'none'; // Скрываем кнопку "Начать"

            // Показываем фотографии
            const photos = document.querySelectorAll('.photo');
            photos.forEach(photo => {
                photo.style.display = 'block';
            });

            // Обработчики событий для drag and drop
            dropArea.addEventListener('dragover', (event) => {
                event.preventDefault();
                resetTimer();
            });

            dropArea.addEventListener('drop', (event) => {
                event.preventDefault();
                const imgSrc = event.dataTransfer.getData("text");
                const imgElements = document.querySelectorAll('.photo img');

                imgElements.forEach((imgContainer) => {
                    if (imgContainer.src === imgSrc) {
                        const photoDiv = imgContainer.parentElement;
                        const isCorrect = photoDiv.getAttribute('data-correct') === 'true';

                        if (isCorrect) {
                            // Удаляем изображение из контейнера .images
                            photoDiv.remove();

                            finalMessage.innerText = "Это нужно на фронте";
                            finalMessage.style.display = 'block';
                            imagesDropped++;

                            if (imagesDropped === totalCorrectImages) {
                                setTimeout(() => {
                                    fighterMessage.style.display = 'block';
                                    buttonReboot.style.display = 'block';
                                    document.body.classList.add('closed-box'); // Добавляем класс к body

                                    const allPhotos = document.querySelectorAll('.photo');
                                    allPhotos.forEach(photo => photo.remove());
                                }, 2000);
                            }

                            setTimeout(() => {
                                finalMessage.style.display = 'none';
                            }, 2000);

                        } else {
                            finalMessage.innerText = "На фронте это не нужно";
                            finalMessage.style.display = 'block';
                            setTimeout(() => {
                                finalMessage.style.display = 'none';
                            }, 2000);

                            if (imagesDropped === totalCorrectImages) {
                                finalMessage.style.display = 'none';
                            }
                        }
                    }
                });
                resetTimer();
            });

            // Обработчик события для кнопки "вернуться"
            buttonReboot.addEventListener('click', () => {
                location.reload(); // Перезагрузка страницы
            });
        });

        const photos = <?php echo json_encode($imagesData); ?>; // Данные из PHP

        function getRandomPosition(containerWidth, containerHeight, photoWidth, photoHeight) {
            let x = Math.random() * (containerWidth - photoWidth);
            let y = Math.random() * (containerHeight - photoHeight);
            if (((270 < x) && (x < 1100)) && (y < 380)) {
                if (685 < x) {
                    x = Math.round(Math.random() * (1300 - 1070) + 1100);
                    y = Math.round(y);
                } 
                if (x < 685) {
                    x = Math.round(Math.random() * 270) ;
                    y = Math.round(y);
                }
            } 
            return { x, y };
        }



        function isOverlapping(newPos, existingPhotos, photoWidth, photoHeight) {
            for (const photo of existingPhotos) {
                if (
                    newPos.x < photo.x + photoWidth &&
                    newPos.x + photoWidth > photo.x &&
                    newPos.y < photo.y + photoHeight &&
                    newPos.y + photoHeight > photo.y
                ) {
                    return true; // Наложение обнаружено
                }
            }
            return false; // Наложений нет
        }

        const placedPhotos = [];

        // Создаем фотографии, но скрываем их до нажатия кнопки "Начать"
        photos.forEach(photoData => {
            const photoDiv = document.createElement('div');
            photoDiv.className = 'photo';
            photoDiv.style.display = 'none'; // Скрываем фотографии по умолчанию
            photoDiv.setAttribute('data-correct', photoData.correct ? 'true' : 'false');

            const imgElement = document.createElement('img');
            imgElement.src = photoData.url;
            imgElement.draggable = true;
            imgElement.addEventListener('dragstart', drag);
            photoDiv.appendChild(imgElement);

            const tempImg = new Image();
            tempImg.src = photoData.url;
            tempImg.onload = () => {
                const photoWidth = Math.min(tempImg.width * 0.25, imagesContainer.clientWidth);
                const photoHeight = Math.min(tempImg.height * 0.25, imagesContainer.clientHeight);

                photoDiv.style.width = `${photoWidth}px`;
                photoDiv.style.height = `${photoHeight}px`;

                let position;
                let attempts = 0;
                const maxAttempts = 500000;

                do {
                    position = getRandomPosition(imagesContainer.clientWidth, imagesContainer.clientHeight, photoWidth, photoHeight);
                    attempts++;
                    if (attempts > maxAttempts) {
                        console.warn('Не удалось найти позицию для изображения без наложения');
                        break;
                    }
                } while (isOverlapping(position, placedPhotos, photoWidth, photoHeight));

                if (position) {
                    placedPhotos.push(position);
                    photoDiv.style.left = `${position.x}px`;
                    photoDiv.style.top = `${position.y}px`;
                    console.log(position.x,position.y)
                    imagesContainer.appendChild(photoDiv);
                }
            };

        });

        // Обновляем totalImages после загрузки всех изображений
        totalImages = document.querySelectorAll('.photo').length;
    </script>
</body>

</html>