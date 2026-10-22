
document.addEventListener('submit', async function (event) {
    const form = event.target;

    // Hantera endast favoritformulär
    if (!form.matches('.favorite-form')) {
        return;
    }

    // Förhindra vanlig formulärinlämning och omladdning
    event.preventDefault();

    const button = form.querySelector('.favorite-button');

    if (!button) {
        console.error('Favoritknappen hittades inte.');
        return;
    }

    // Font Awesome kan använda både SVG och I-element
    const icon = button.querySelector('svg, i');

    if (!icon) {
        console.error('Favoritikonen hittades inte.');
        return;
    }

    // Förhindra dubbla klick under pågående anrop
    if (button.disabled) {
        return;
    }

    // Kontrollera om stjärnan är fylld
    const wasFavorite =
        icon.classList.contains('fa-solid') ||
        icon.classList.contains('fas') ||
        icon.getAttribute('data-prefix') === 'fas';

    // Förbered formulärdata
    const formData = new FormData(form);

    const action = wasFavorite
        ? 'remove_favorite'
        : 'add_favorite';

    formData.set('action', action);

    button.disabled = true;

    try {
        const response = await fetch('favorites.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const responseText = await response.text();

        let result;

        try {
            result = JSON.parse(responseText);
        } catch (error) {
            console.error(
                'favorites.php returnerade inte giltig JSON:',
                responseText
            );

            throw new Error(
                'Servern returnerade ett ogiltigt svar.'
            );
        }

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || `Serverfel: ${response.status}`
            );
        }

        // Uppdatera stjärnan efter serverns bekräftelse
        icon.classList.toggle('fa-solid', result.isFavorite);
        icon.classList.toggle('fa-regular', !result.isFavorite);

        button.setAttribute(
            'aria-pressed',
            String(result.isFavorite)
        );

        button.setAttribute(
            'aria-label',
            result.isFavorite
                ? 'Ta bort favorit'
                : 'Lägg till favorit'
        );

        button.setAttribute(
            'title',
            result.isFavorite
                ? 'Ta bort favorit'
                : 'Lägg till favorit'
        );

    } catch (error) {
        console.error('Fel vid hantering av favorit:', error);
    } finally {
        button.disabled = false;
    }
});
