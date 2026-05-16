<script setup>
  // Composant d'affichage graphique des résultats (barres de progression)
  // Reçoit les données depuis AppPollVote, mis à jour automatiquement via polling
  defineProps({
    results: { type: Object, required: true },
  });

  // Palette de couleurs pour différencier les options visuellement
  const colors = [
    'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-400',
    'bg-pink-500', 'bg-teal-500', 'bg-yellow-400', 'bg-red-400',
  ];
</script>

<template>
  <div class="space-y-3">
    <!-- Une barre par option, avec label, pourcentage et nombre de votes -->
    <div v-for="(opt, i) in results.options" :key="opt.id">
      <div class="flex justify-between text-sm text-gray-700 mb-1">
        <span>{{ opt.label }}</span>
        <span class="font-medium">
          {{ opt.percentage }}%
          <span class="text-gray-400 font-normal">({{ opt.votes_count }})</span>
        </span>
      </div>
      <!-- Barre de progression animée -->
      <div class="w-full bg-gray-100 rounded-full h-5 overflow-hidden">
        <div
          class="h-5 rounded-full transition-all duration-500"
          :class="colors[i % colors.length]"
          :style="{ width: opt.percentage + '%' }"
        ></div>
      </div>
    </div>

    <!-- Indication selon l'état du sondage -->
    <p v-if="results.is_expired" class="text-xs text-gray-400 text-right pt-1">
      Résultats définitifs
    </p>
    <p v-else class="text-xs text-gray-400 text-right pt-1">
      Mis à jour automatiquement toutes les 5 secondes…
    </p>
  </div>
</template>