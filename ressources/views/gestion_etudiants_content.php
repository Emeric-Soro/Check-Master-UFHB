<div class="container mx-auto px-4 py-8">


    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Carte pour ajouter un étudiant -->
        <a href="?page=gestion_etudiants&action=ajouter_des_etudiants" class="block">
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user-plus text-white text-xl"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800">Ajouter un Étudiant</h2>
                    </div>
                    <p class="text-gray-600 mb-4">
                        Créez un nouveau profil étudiant dans la base de données. Cette option permet d'ajouter
                        manuellement les informations d'un nouvel étudiant.
                    </p>
                    <div class="flex items-center text-blue-600 font-medium">
                        Accéder à l'ajout d'étudiant
                        <i
                            class="fas fa-arrow-right ml-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                    </div>
                </div>
            </div>
        </a>

        <!-- Carte pour inscrire un étudiant -->
        <a href="?page=gestion_etudiants&action=inscrire_des_etudiants" class="block">
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user-graduate text-white text-xl"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800">Inscrire un Étudiant</h2>
                    </div>
                    <p class="text-gray-600 mb-4">
                        Inscrivez un étudiant à une formation ou un cours. Cette option permet de gérer les inscriptions
                        académiques des étudiants existants.
                    </p>
                    <div class="flex items-center text-blue-600 font-medium">
                        Accéder aux inscriptions
                        <i
                            class="fas fa-arrow-right ml-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
}

.grid {
    display: grid;
}

.grid-cols-1 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
    .md\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.gap-6 {
    gap: 1.5rem;
}

.bg-white {
    background-color: white;
}

.rounded-lg {
    border-radius: 0.5rem;
}

.shadow-md {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.hover\:shadow-lg:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.transition-shadow {
    transition-property: box-shadow;
}

.duration-300 {
    transition-duration: 300ms;
}

.overflow-hidden {
    overflow: hidden;
}

.p-6 {
    padding: 1.5rem;
}

.flex {
    display: flex;
}

.items-center {
    align-items: center;
}

.mb-4 {
    margin-bottom: 1rem;
}

.mr-4 {
    margin-right: 1rem;
}

.w-12 {
    width: 3rem;
}

.h-12 {
    height: 3rem;
}



.bg-blue-500 {
    background-color: #3B82F6;
}

.rounded-full {
    border-radius: 9999px;
}

.text-white {
    color: white;
}

.text-xl {
    font-size: 1.25rem;
    line-height: 1.75rem;
}

.font-semibold {
    font-weight: 600;
}

.text-gray-800 {
    color: #1F2937;
}

.text-gray-600 {
    color: #4B5563;
}

.text-blue-600 {
    color: #2563EB;
}

.font-medium {
    font-weight: 500;
}

.ml-2 {
    margin-left: 0.5rem;
}

.group-hover\:translate-x-1:hover {
    transform: translateX(0.25rem);
}

.transition-transform {
    transition-property: transform;
}
</style>