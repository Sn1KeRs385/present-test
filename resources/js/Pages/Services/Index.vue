<script setup>
import { router } from '@inertiajs/vue3'
const props = defineProps({
  services: { type: Array, required: true },
  selectedOption: { type: Object, default: null }
})
const go = (optionId) => {
  router.visit('/', { data: { optionId } })
}
</script>

<template>
  <div class="p-6 max-w-3xl mx-auto space-y-6">
    <h1 class="text-2xl font-semibold">Выберите услугу</h1>
    <div v-for="s in services" :key="s.id" class="border rounded p-4">
      <div class="text-lg font-medium mb-2">{{ s.name }}</div>
      <div class="flex flex-wrap gap-2">
        <button v-for="o in s.options" :key="o.id" @click="$inertia.visit(route('services.index'), { data: { option: o.id } })" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          {{ o.name }}
        </button>
      </div>
    </div>

    <BookingsSchedule v-if="selectedOption" :option="selectedOption" />
  </div>
</template>

<script>
import BookingsSchedule from '../Bookings/Schedule.vue'
export default { components: { BookingsSchedule } }
</script>
