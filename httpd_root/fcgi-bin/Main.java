import com.fastcgi.FCGIInterface;

import java.io.IOException;
import java.nio.ByteBuffer;
import java.nio.charset.StandardCharsets;

public class Main {

    public static void main(String[] args) {
        var fcgiInterface = new FCGIInterface();
        while (fcgiInterface.FCGIaccept() >= 0) {
            processRequest();
        }
    }

    private static void processRequest() {
        try {
            long startTime = System.nanoTime();
            String[] params = RequestReader.parseRequest(RequestReader.readRequestBody()); // {x, y, r}
            float[] result = ParameterValidator.validate(params); // {x, y, r}
            String answer = String.format("{\"isHit\": %b, \"curTime\": \"%s\", \"dur\": %s}",
                    HitChecker.checkResult(result[0], result[1], result[2]), // [0] - x, [1] - y, [2] - r
                    System.currentTimeMillis(),
                    (System.nanoTime() - startTime) / 1e6);
            ResponseFormatter.sendResponse(answer);
        }
        catch (CustomException e) {
            ResponseFormatter.printError(e.getMessage());
        } catch (NumberFormatException e) {
            ResponseFormatter.printError("Некорректный формат числа, NumberFormatException" + e.getMessage());
        } catch (IOException e) {
            ResponseFormatter.printError("Ошибка ввода/вывода, IOException: " + e.getMessage());
        } catch (Exception e) {
            ResponseFormatter.printError("Внутренняя ошибка сервера" + e.getMessage());
        }
    }
}

class CustomException extends Exception {
    public CustomException(String message) {
        super(message);
    }
}

class RequestReader {
    static String readRequestBody() throws CustomException, IOException {
        FCGIInterface.request.inStream.fill();
        var contentLength = FCGIInterface.request.inStream.available();
        var buffer = ByteBuffer.allocate(contentLength);
        var readBytes = FCGIInterface.request.inStream.read(buffer.array(), 0, contentLength);
        if (readBytes < 0) throw new CustomException("Ошибка при чтении потока, readBytes < 0");
        var requestBodyRaw = new byte[readBytes];
        buffer.get(requestBodyRaw);
        buffer.clear();
        return new String(requestBodyRaw, StandardCharsets.UTF_8);
    }

    public static String[] parseRequest(String requestBody) throws CustomException {
        String x = null, y = null, r = null;
        String[] data = requestBody.split("😁");
        if (data.length != 3)
            throw new CustomException("Неверное количество аргументов");
        for (String pair : data) {
            String[] keyValue = pair.split("=");
            if (keyValue.length != 2)
                throw new CustomException("Неверный формат аргумента: " + pair);
            switch (keyValue[0]) {
                case "x" -> x = keyValue[1];
                case "y" -> y = keyValue[1];
                case "r" -> r = keyValue[1];
                default -> throw new CustomException("Неверный аргумент: " + keyValue[0]);
            }
        }
        if (x == null || y == null || r == null)
            throw new CustomException("Неверные аргументы: x, y и r обязательны");
        return new String[] {x, y, r};
    }
}

class ParameterValidator {
    public static float[] validate(String[] params) throws CustomException {
        float x = Float.parseFloat(params[0]);
        float y = Float.parseFloat(params[1]);
        float r = Float.parseFloat(params[2]);
        if (x < -4 || x > 4 || y < -3 || y > 5 || r < 1 || r > 4)
            throw new CustomException("Значения находятся в неверном диапазоне");
        return new float[] {x, y, r};
    }
}

class ResponseFormatter {
    static String HttpResponse = "Content-Type: application/json\n" +
            "Content-Length: %d\n\n%s";

    public static void printError(String message) {
        String answer = String.format("{\"error\": {\"message\": \"%s\"}}", message);
        System.out.printf(HttpResponse, answer.getBytes(StandardCharsets.UTF_8).length, answer);
    }

    public static void sendResponse(String answer) {
        System.out.printf(HttpResponse, answer.getBytes(StandardCharsets.UTF_8).length, answer);
    }
}
class HitChecker {
    public static boolean checkResult(float x, float y, float r) {
        if (x <= 0 && y <= 0 && x >= -r && y >= -r / 2) return true;
        if (x <= 0 && y >= 0 && -x + y <= r) return true;
        if (x >= 0 && y >= 0 && r * r - (x * x + y * y) >= 0) return true;
        return false;
    }
}