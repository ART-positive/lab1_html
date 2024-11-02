function limitDigits(event) {
  const input = event.currentTarget;
  const maxDigits = 8;
  if (input.value.length > maxDigits) {
    input.value = input.value.slice(0, maxDigits);
  }
}

document.getElementById('y').addEventListener('input', limitDigits);
document.getElementById('r').addEventListener('input', limitDigits);

const timeOptions = {
  year: 'numeric',
  month: 'numeric',
  day: 'numeric',
  hour: 'numeric',
  minute: 'numeric',
  second: 'numeric',
};

function populateTable(data) {
  const tableBody = document.querySelector('#resultsTable tbody');
  const row = document.createElement('tr');
  const {
    x,
    y,
    r,
    isHit,
    curTime,
    dur,
  } = data.at(-1);
  const cellData = [
    x,
    y,
    r,
    isHit ? 'Да' : 'Нет',
    new Intl.DateTimeFormat('ru-RU', timeOptions).format(new Date(+curTime)),
    `${dur} мс`,
  ];
  cellData.forEach((content) => {
    const cell = document.createElement('td');
    cell.textContent = content;
    row.appendChild(cell);
  });
  tableBody.appendChild(row);
}

const result = [];

const minY = -3;
const maxY = 5;
const minR = 1;
const maxR = 4;
const inputY = document.getElementById('y');
const inputR = document.getElementById('r');

function validateInput(input, min, max) {
  const { value } = input;
  if (value.trim() === '') {
    input.setCustomValidity('Некорректное значение!');
    return false;
  }
  if (value.toString().includes('e')) {
    input.setCustomValidity('What are you doing??🤨');
    return false;
  }
  if (value < min) {
    input.setCustomValidity(`Значение слишком маленькое, должно быть не меньше ${min}`);
    return false;
  }
  if (value > max) {
    input.setCustomValidity(`Значение слишком большое, должно быть не больше ${max}`);
    return false;
  }
  input.setCustomValidity('');
  return true;
}

inputY.addEventListener('input', () => validateInput(inputY, minY, maxY));
inputR.addEventListener('input', () => validateInput(inputR, minR, maxR));

function showNotification(message) {
  const notification = document.createElement('div');
  notification.className = 'notification';
  notification.innerText = message;

  document.getElementById('notification-container').appendChild(notification);
  setTimeout(() => {
    document.getElementById('notification-container').removeChild(notification);
  }, 5000);
}

function handleError(status) {
  const message = {
    500: 'Ошибка сервера (500). Попробуйте позже.',
    502: 'Плохой шлюз (502). Проблема с сервером. Попробуйте позже.',
    503: 'Служба недоступна (503). Сервер перегружен или временно недоступен.',
    504: 'Время ожидания шлюза истекло (504). Попробуйте позже.',
  };
  showNotification(message[status] || `Произошла ошибка. Код ошибки: ${status}`);
}

function responseData(data) {
  if (data.error) {
    showNotification(data.error.toString());
  } else {
    result.push({
      x: document.getElementById('x').value,
      y: (+document.getElementById('y').value).toString(),
      r: (+document.getElementById('r').value).toString(),
      isHit: data.isHit,
      curTime: data.curTime,
      dur: data.dur,
    });
    populateTable(result);
  }
}

async function handleSubmit(event) {
  event.preventDefault();
  if (!validateInput(inputY, minY, maxY) || !validateInput(inputR, minR, maxR)) {
    return;
  }

  const formData = Object.fromEntries(new FormData(document.getElementById('dataForm')));
  const body = Object.keys(formData).map((e) => `${e}=${formData[e]}`).join('😁');

  try {
    const response = await fetch('/fcgi-bin/labwork1.jar', {
      method: 'POST',
      body,
      headers: {
        'Content-Type': 'application/json',
      },
    });
    if (!response.ok) {
      handleError(response.status);
      return;
    }
    const data = await response.json();
    responseData(data);
  } catch (error) {
    showNotification(error.toString());
  }
}

document.getElementById('dataForm').addEventListener('submit', handleSubmit);
