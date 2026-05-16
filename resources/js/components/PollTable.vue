<script setup>
  import { usePollStore } from '@/stores/usePollStore';

  const emit = defineEmits(['edit', 'create']);
  const { polls, deletePoll, launchPoll } = usePollStore();

  async function delPoll(id) {
    if (!confirm('Supprimer ce sondage ?')) return;
    await deletePoll(id);
  }

  async function handleLaunch(poll) {
    if (!confirm('Lancer ce sondage maintenant ?')) return;
    await launchPoll(poll.id);
  }

  async function copyShareLink(poll) {
    const url = `${window.location.origin}/polls/vote/${poll.secret_token}`;
    try {
      await navigator.clipboard.writeText(url);
      alert('Lien copié !');
    } catch {
      alert('Lien : ' + url);
    }
  }

  function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('fr-CH', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    });
  }

  function isExpired(poll) {
    return poll.ends_at && new Date(poll.ends_at) < new Date();
  }
</script>

<template>
  <!-- Liste vide -->
  <div v-if="polls.length === 0" class="text-center py-16 text-gray-400">
    <p class="text-lg mb-3">Aucun sondage pour l'instant.</p>
    <button @click="emit('create')" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 text-sm">
      Créer mon premier sondage
    </button>
  </div>

  <!-- Cartes -->
  <div v-else class="space-y-3">
    <div
      v-for="poll in polls" :key="poll.id"
      class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm"
    >
      <div class="flex items-start justify-between gap-4">

        <!-- Infos -->
        <div class="flex-1 min-w-0">
          <!-- Badge statut -->
          <span v-if="poll.is_draft" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Brouillon</span>
          <span v-else-if="isExpired(poll)" class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-600">Terminé</span>
          <span v-else class="text-xs font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-700">Actif</span>

          <h2 class="font-semibold text-gray-800 mt-1 truncate">{{ poll.title || poll.question }}</h2>
          <p v-if="poll.title" class="text-sm text-gray-500 truncate">{{ poll.question }}</p>
          <p class="text-xs text-gray-400 mt-1">
            Début : {{ formatDate(poll.started_at) }}
            <span v-if="poll.ends_at"> · Fin : {{ formatDate(poll.ends_at) }}</span>
          </p>
        </div>

        <!-- Boutons -->
        <div class="flex flex-col gap-2 flex-shrink-0">
          <div class="flex gap-2">
            <button @click="emit('edit', poll)"
              class="text-xs border border-gray-300 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-50">
              Éditer
            </button>
            <button @click="delPoll(poll.id)"
              class="text-xs border border-red-200 text-red-500 px-3 py-1.5 rounded-lg hover:bg-red-50">
              Supprimer
            </button>
          </div>
          <button v-if="poll.is_draft" @click="handleLaunch(poll)"
            class="text-xs bg-teal-600 text-white px-3 py-1.5 rounded-lg hover:bg-teal-700 text-center">
            🚀 Lancer le sondage
          </button>
          <button v-else @click="copyShareLink(poll)"
            class="text-xs bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-100 text-center">
            🔗 Copier le lien
          </button>
        </div>

      </div>
    </div>
  </div>
</template>