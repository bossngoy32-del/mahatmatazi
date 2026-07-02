<footer class="bg-dark text-white text-center py-4 mt-5">
    <div class="container">
        <p class="mb-0">&copy; <?= date('Y') ?> - Professeur Mahatma Julien Tazi Kizey Tien-a-be. Tous droits réservés.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sweetalert2/sweetalert2.min.css"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const detailButtons = document.querySelectorAll('.btn-details');
    
    detailButtons.forEach(button => {
        button.addEventListener('click', function () {
            const titre = this.getAttribute('data-titre');
            
            // Alerte interactive avec SweetAlert 
            Swal.fire({
                title: titre,
                text: "Pour obtenir un exemplaire ou accéder aux ressources complètes de cette publication, veuillez contacter le secrétariat académique.",
                icon: "info",
                confirmButtonText: "Fermer",
                confirmButtonColor: "#0d6efd"
            });
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnTest = document.getElementById('btn-test-alert');
    
    if (btnTest) {
        btnTest.addEventListener('click', function () {
            // Déclenchement d'une alerte SweetAlert2
            Swal.fire({
                title: 'Bravo !',
                text: 'SweetAlert2 fonctionne parfaitement en local !',
                icon: 'success',
                confirmButtonText: 'Super',
                confirmButtonColor: '#198754'
            });
        });
    }
});
</script>

</body>
</html>