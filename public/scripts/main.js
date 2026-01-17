const search = document.querySelector('input[placeholder="search card"]');
const cardsContainer = document.querySelector(".cards");
const template = document.querySelector("#card-template");
search.addEventListener("keyup", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();

        const data = { search: this.value };

        fetch("/search-cards", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(function (response) {
                return response.json();    // <-- tu parsujemy JSON z PHP
            })
            .then(function (cards) {
                cardsContainer.innerHTML = "";
                loadCards(cards);
            });
    }
});

function loadCards(cards) {
    cards.forEach(card => {
        console.log(card);
        createCard(card);
    });
}

function createCard(card) {
    const template = document.querySelector("#card-template");

    const clone = template.content.cloneNode(true);
    const div = clone.querySelector("div");
    div.id = card.id;

    const image = clone.querySelector("img");
    image.src = card.image;

    const title = clone.querySelector("h2");
    title.innerHTML = card.title;

    const description = clone.querySelector("p");
    description.innerHTML = card.description;

    cardsContainer.appendChild(clone);
}

