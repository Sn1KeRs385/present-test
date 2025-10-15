<script setup>
import axios from 'axios'
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  option: { type: Object, required: true }
})

const today = new Date()
const startOfWeek = ref(getStartOfWeek(today))
const selectedDate = ref(formatDate(today))
const slots = ref([])
const loading = ref(false)
const name = ref('')
const phone = ref('')
const showSuccess = ref(false)

function getStartOfWeek(d){
  const date = new Date(d)
  const day = date.getDay() === 0 ? 7 : date.getDay()
  const diff = day - 1
  date.setDate(date.getDate() - diff)
  date.setHours(0,0,0,0)
  return date
}
function addDays(d, n){ const x = new Date(d); x.setDate(x.getDate()+n); return x }
function formatDate(d){ return new Date(d).toISOString().slice(0,10) }
function formatHuman(d){ return new Date(d).toLocaleDateString('ru-RU', { day:'2-digit', month:'2-digit' }) }

const days = computed(()=> Array.from({length:7}, (_,i)=> addDays(startOfWeek.value, i)))

async function loadSlots(){
  loading.value = true
  try{
    const { data } = await axios.get(route('availability'), { params: { service_option_id: props.option.id, date: selectedDate.value } })
    slots.value = data.slots
  } finally {
    loading.value = false
  }
}

function prevWeek(){ startOfWeek.value = addDays(startOfWeek.value, -7) }
function nextWeek(){ startOfWeek.value = addDays(startOfWeek.value, 7) }

watch([startOfWeek], ()=>{
  if (selectedDate.value < formatDate(startOfWeek.value) || selectedDate.value > formatDate(addDays(startOfWeek.value,6))){
    selectedDate.value = formatDate(startOfWeek.value)
  }
  loadSlots()
})
watch(selectedDate, loadSlots)

onMounted(loadSlots)

async function book(t){
  const d = selectedDate.value
  const form = { service_option_id: props.option.id, date: d, time: t, name: name.value, phone: phone.value }
  await axios.post(route('bookings.store'), form)
  showSuccess.value = true
  setTimeout(()=>{ router.visit('/') }, 2000)
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <button class="px-3 py-2 border rounded" @click="prevWeek">Пред. неделя</button>
      <div class="font-medium">Неделя: {{ formatHuman(days[0]) }} — {{ formatHuman(days[6]) }}</div>
      <button class="px-3 py-2 border rounded" @click="nextWeek">След. неделя</button>
    </div>
    <div class="grid grid-cols-7 gap-2">
      <button v-for="d in days" :key="d.toISOString()" @click="selectedDate = formatDate(d)"
              class="p-2 border rounded"
              :class="selectedDate === formatDate(d) ? 'bg-blue-600 text-white' : ''">
        {{ d.toLocaleDateString('ru-RU', { weekday:'short', day:'2-digit' }) }}
      </button>
    </div>

    <div class="border rounded p-4">
      <div class="mb-2">Свободные слоты на {{ selectedDate }}</div>
      <div v-if="loading">Загрузка...</div>
      <div v-else class="flex flex-wrap gap-2">
        <button v-for="t in slots" :key="t" class="px-3 py-2 bg-emerald-600 text-white rounded" @click="book(t)">{{ t }}</button>
        <div v-if="!slots.length">Нет доступных слотов</div>
      </div>
    </div>

    <div class="border rounded p-4 space-y-2">
      <input v-model="name" placeholder="Имя" class="border rounded px-3 py-2 w-full" />
      <input v-model="phone" placeholder="Телефон" class="border rounded px-3 py-2 w-full" />
    </div>

    <div v-if="showSuccess" class="fixed inset-0 bg-black/40 flex items-center justify-center">
      <div class="bg-white p-6 rounded shadow">
        <div class="text-lg mb-2">Бронирование успешно!</div>
        <div>Сейчас вы будете перенаправлены...</div>
      </div>
    </div>
  </div>
</template>
