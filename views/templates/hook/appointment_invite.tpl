<div class="appointment-invite-container">
    <h2>Besoin d'un rendez-vous ?</h2>
    <p>Nous sommes à votre disposition pour organiser un rendez-vous à votre convenance.</p>
    <!-- Le bouton renvoie vers le formulaire (l'URL est passée via $appointment_link) -->
    <a href="{$appointment_link}" class="btn btn-primary btn-lg">Prendre un rendez-vous</a>
</div>

<style>
.appointment-invite-container {
    text-align: center;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 10px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    margin: 40px auto;
}
.appointment-invite-container h2 {
    color: #333;
}
.appointment-invite-container p {
    color: #555;
}
.appointment-invite-container .btn {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    transition: 0.3s;
}
.appointment-invite-container .btn:hover {
    background-color: #0056b3;
}
</style>