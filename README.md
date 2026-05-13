## Установка и запуск

- `git clone https://github.com/KharchenkoDev/dispa-test.git`
- `cd dispa-test`
- `make init`
- Прописать токен DaData в `app/.env`: `DADATA_TOKEN=ваш_токен`

Токен можно получить на [dadata.ru](https://dadata.ru) после регистрации.

## API

`GET /api/inn/{inn}` — поиск организации по ИНН (10 или 12 цифр).

```json
{
    "inn": "7707083893",
    "name": "ПАО Сбербанк",
    "is_active": true,
    "okved": "64.19",
    "okved_name": "Деятельность по предоставлению прочих видов кредита"
}
```
