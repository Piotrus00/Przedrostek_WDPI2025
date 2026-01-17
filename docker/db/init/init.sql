CREATE TABLE users (
                       id SERIAL PRIMARY KEY,
                       firstname VARCHAR(100) NOT NULL,
                       lastname VARCHAR(100) NOT NULL,
                       email VARCHAR(150) UNIQUE NOT NULL,
                       password VARCHAR(255) NOT NULL,
                       bio TEXT,
                       enabled BOOLEAN DEFAULT TRUE
);

INSERT INTO users (firstname, lastname, email, password, bio, enabled)
VALUES (
           'Jan',
           'Kowalski',
           'jan.kowalski@example.com',
           '$2b$10$ZbzQrqD1vDhLJpYe/vzSbeDJHTUnVPCpwlXclkiFa8dO5gOAfg8tq',
           'Lubi programować w JS i PL/SQL.',
           TRUE
       );
INSERT INTO users (firstname, lastname, email, password, bio, enabled)
VALUES (
    'Test',
    'User',
    'test.user@example.com',
    '$2b$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36E8Fz8D2N9b8KZ9jF1e0yC',
    'Użytkownik testowy do logowania.',
    TRUE
);

