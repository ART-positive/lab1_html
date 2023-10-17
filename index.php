<?php
session_start(); // Запускаем сессию для хранения предыдущих результатов

function Square($x1, $y1, $x2, $y2, $x3, $y3)
{
    return abs(($x1-$x3)*($y2-$y3)-($x2-$x3)*($y1-$y3));
}
// Функция для проверки, попадает ли точка в заданную область
function isPointInArea($x, $y, $r): bool
{
    if($x > 0 && $y > 0) return false;
    else if($x < 0 && $y < 0) {
        if(-$r <= $x && -$r/2 <= $y) return true;
        else return false;
    }
    else if($x < 0 && $y > 0){
        if(abs(($r * $r)
                - Square(0, 0, 0, $r, $x, $y)
                - Square(0, 0, -$r, 0, $x, $y)
                - Square(-$r, 0, 0, $r, $x, $y)) < 1e6) return true;
        else return false;
    }
    else if($x > 0 && $y < 0){
        if(sqrt($x * $x + $y * $y) <= $r) return true;
        else return false;
    }
    else {
        if($x + $y <= $r / 2) return true;
        if(-$r <= $x && $x <= 0) return true;
        if($r >= $y && $y >= 0) return true;
    }
    return false;
}

// Получаем параметры из GET-запроса
if (isset($_GET["x"]) && isset($_GET["y"]) && isset($_GET["r"])) {
    $x = floatval($_GET["x"]);
    $y = floatval($_GET["y"]);
    $r = floatval($_GET["r"]);

    // Проверяем попадание точки в область
    $isInArea = isPointInArea($x, $y, $r);

    // Генерируем текущее время и время работы скрипта
    $currentDateTime = date("Y-m-d H:i:s");
    $scriptExecutionTime = sprintf("%.6f", microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);

    // Добавляем результат в сессию для отображения на странице
    $_SESSION["results"][] = [
        "x" => $x,
        "y" => $y,
        "r" => $r,
        "isInArea" => $isInArea,
        "currentDateTime" => $currentDateTime,
        "scriptExecutionTime" => $scriptExecutionTime
    ];
}



?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>HTML5</title>
    <style>
        article, aside, details, figcaption, figure, footer, header,
        hgroup, menu, nav, section {
            display: block;
        }
    </style>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <h1>Березовский Артемий Сергеевич</h1>
    <p>Группа: P3232</p>
    <p>Вариант: 1300</p>
</header>
<div class="container">
    <div class="block">
        <img src="image.png" alt="Описание картинки">
    </div>
    <div class="my-form">
        <form action="" method="get" >
            <div class="vertical-block">
            <label for="x"></label><select name = "x" id = "x">
                <option value="-2.0">-2.0</option>
                <option value="-1.5">-1.5</option>
                <option value="-1.0">-1.0</option>
                <option value="-0.5">-0.5</option>
                <option value="0.0">0.0</option>
                <option value="0.5">0.5</option>
                <option value="1.0">1.0</option>
                <option value="1.5">1.5</option>
                <option value="2.0">2.0</option></select>
            </div>
            <div class="vertical-block">
                <label for="y"></label><input type="number" name = "y" id = "y" step="any" placeholder="Y">
            </div>
            <div class="vertical-block">
                <label for="r"></label><input type="number" name = "r" id = "r" step="any" placeholder="R">
            </div>
            <div class="center-button">
                <input type="submit" class = "button" value="Отправить" id="submitButton">
            </div>
        </form>
    <!--    <button id="clearTableButton">Очистить таблицу</button>-->
        <form method="post">
            <input type="submit" class = "button" name="clearSession" value="Очистить таблицу">
        </form>
        <?php
        session_start(); // Запуск сессии

        if (isset($_POST["clearSession"])) {
            // Удаление переменных сессии
            session_unset();

            // Опционально: полная очистка сессии
            // session_destroy();

            // Перенаправление пользователя обратно на страницу с кнопкой
            header("Location: index.php");
            exit;
        }
        ?>
    </div>
</div>
        <h2 align="center">Результаты</h2>
        <table align="center" id = "myTable">
            <thead>
            <tr>
                <th>X</th>
                <th>Y</th>
                <th>R</th>
                <th>Попадание в область</th>
                <th>Время выполнения</th>
                <th>Текущее время</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if (isset($_SESSION["results"])) {
                foreach ($_SESSION["results"] as $result) {
                    $x = $result["x"];
                    $y = $result["y"];
                    $r = $result["r"];
                    $isInArea = $result["isInArea"];
                    $currentDateTime = $result["currentDateTime"];
                    $scriptExecutionTime = $result["scriptExecutionTime"];

                    echo "<tr>";
                    echo "<td>{$x}</td>";
                    echo "<td>{$y}</td>";
                    echo "<td>{$r}</td>";
                    echo "<td>" . ($isInArea ? "Да" : "Нет") . "</td>";
                    echo "<td>{$scriptExecutionTime}</td>";
                    echo "<td>{$currentDateTime}</td>";
                    echo "</tr>";
                }
            }

            ?>
            </tbody>
        </table>

        <script>
            const minY = -5, maxY = 5;
            const minR = 2, maxR = 5;

            const inputY = document.getElementById("y");
            const inputR = document.getElementById("r");

            inputY.addEventListener("input", () => validateY());
            validateY();

            inputR.addEventListener("input", () => validateR());
            validateR();

            function validateY() {
                if (inputY.value === "")
                    inputY.setCustomValidity("Значение не введено!");
                else if (isNaN(inputY.value.includes(".")) || isNaN(inputY.value.replace(",", ".")))
                    inputY.setCustomValidity("Значение должно быть числом!");
                else {
                    const value = inputY.value;
                    // console.log(value); - вывести в консоль
                    if (value < minY)
                        inputY.setCustomValidity("Значение слишком маленькое, должно быть не меньше " + minY);
                    else if (value > maxY)
                        inputY.setCustomValidity("Значение слишком большое, должно быть не больше " + maxY);
                    else {
                        inputY.setCustomValidity("");
                        return true;
                    }
                }
                return false;
            }

            function validateR() {
                if (inputR.value === "")
                    inputR.setCustomValidity("Значение не введено!");
                else if (isNaN(inputR.value.includes(".")) || isNaN(inputR.value.replace(",", ".")))
                    inputR.setCustomValidity("Значение должно быть числом!");
                else {
                    const value = inputR.value;
                    // console.log(value); - вывести в консоль
                    if (value < minR)
                        inputR.setCustomValidity("Значение слишком маленькое, должно быть не меньше " + minR);
                    else if (value > maxR)
                        inputR.setCustomValidity("Значение слишком большое, должно быть не больше " + maxR);
                    else {
                        inputR.setCustomValidity("");
                        return true;
                    }
                }
                return false;
            }
        </script>
</body>
</html>