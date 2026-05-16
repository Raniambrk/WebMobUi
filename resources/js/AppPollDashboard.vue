<script setup>
  import PollTable from './components/PollTable.vue';
  import PollForm from './components/PollForm.vue';
  import { ref } from 'vue';
  import { usePollStore } from '@/stores/usePollStore';

  const props = defineProps({
    polls:    { type: Array,  default: () => [] },
    loginUrl: { type: String, default: null },
    username: { type: String, default: null },
  });

  const { setPolls } = usePollStore();
  setPolls(props.polls);

  const view = ref('list');
  const editingPoll = ref(null);

  function openCreate() {
    editingPoll.value = null;
    view.value = 'create';
  }

  function openEdit(poll) {
    editingPoll.value = poll;
    view.value = 'edit';
  }

  function backToList() {
    view.value = 'list';
    editingPoll.value = null;
  }
</script>

<template>
  <div>
    <!-- Bouton retour si on est dans le formulaire -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">
        {{ view === 'list' ? 'Mes sondages' : (editingPoll ? 'Modifier le sondage' : 'Nouveau sondage') }}
      </h1>
      <button
        v-if="view === 'list'"
        @click="openCreate"
        class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition text-sm font-medium"
      >
        + Nouveau sondage
      </button>
      <button
        v-else
        @click="backToList"
        class="text-teal-600 hover:underline text-sm"
      >
        ← Retour à la liste
      </button>
    </div>

    <PollTable v-if="view === 'list'" @edit="openEdit" @create="openCreate" />
    <PollForm v-else :poll="editingPoll" @saved="backToList" @cancel="backToList" />
  </div>
</template>