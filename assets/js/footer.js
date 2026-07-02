document.addEventListener("DOMContentLoaded", function () {
    fetch("footer.html")
        .then(response => response.text())
        .then(data => {
            let container = document.getElementById("footer-container");
            if (container) {
                container.innerHTML = data;
            }
        })
        .catch(error => console.error("Error loading footer:", error));
});

