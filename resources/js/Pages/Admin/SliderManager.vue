<template>
  <AdminLayout>
    <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl p-4 rounded-2xl shadow-2xl border border-cyan-300/30 h-full overflow-y-auto">
      <h1 class="text-lg font-bold text-slate-800 drop-shadow-sm mb-4">Slider Management</h1>
          <!-- Desktop Images Section -->
          <div class="mb-8">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-base font-medium text-slate-800 drop-shadow-sm">Desktop Slider Images</h2>
              <button
                @click="showUploadModal('desktop')"
                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
              >
                Add Desktop Image
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <div
                v-for="image in desktopImages"
                :key="image.id"
                class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl shadow-lg overflow-hidden border border-white/30"
              >
                <div class="aspect-video bg-white/20 relative">
                  <img
                    :src="`/storage/${image.image_path}`"
                    :alt="image.title"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute top-2 right-2">
                    <button
                      @click="toggleImage(image)"
                      :class="image.is_active ? 'bg-green-500' : 'bg-gray-400'"
                      class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs"
                    >
                      <svg v-if="image.is_active" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>
                </div>
                <div class="p-4">
                  <h3 class="font-medium text-gray-900">{{ image.title || 'Untitled' }}</h3>
                  <p class="text-sm text-gray-500">Order: {{ image.sort_order }}</p>
                  <div class="mt-3 flex space-x-2">
                    <button
                      @click="editImage(image)"
                      class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteImage(image)"
                      class="text-red-600 hover:text-red-800 text-sm font-medium"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Mobile Images Section -->
          <div class="mb-8">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-base font-medium text-slate-800 drop-shadow-sm">Mobile Slider Images</h2>
              <button
                @click="showUploadModal('mobile')"
                class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg text-sm font-medium transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl"
              >
                Add Mobile Image
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <div
                v-for="image in mobileImages"
                :key="image.id"
                class="bg-gradient-to-r from-white/40 via-white/30 to-white/20 backdrop-blur-sm rounded-xl shadow-lg overflow-hidden border border-white/30"
              >
                <div class="aspect-video bg-white/20 relative">
                  <img
                    :src="`/storage/${image.image_path}`"
                    :alt="image.title"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute top-2 right-2">
                    <button
                      @click="toggleImage(image)"
                      :class="image.is_active ? 'bg-green-500' : 'bg-gray-400'"
                      class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs"
                    >
                      <svg v-if="image.is_active" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>
                </div>
                <div class="p-4">
                  <h3 class="font-medium text-gray-900">{{ image.title || 'Untitled' }}</h3>
                  <p class="text-sm text-gray-500">Order: {{ image.sort_order }}</p>
                  <div class="mt-3 flex space-x-2">
                    <button
                      @click="editImage(image)"
                      class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteImage(image)"
                      class="text-red-600 hover:text-red-800 text-sm font-medium"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

        <!-- Upload/Edit Modal -->
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black bg-opacity-40 overflow-y-auto h-full w-full z-50"
        @click="closeModal"
      >
        <div class="relative top-20 mx-auto p-6 w-96 shadow-2xl rounded-lg bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl border border-white/20" @click.stop>
          <div class="mt-3">
            <h3 class="text-lg font-semibold text-white mb-4">
              {{ isEditing ? 'Edit' : 'Upload' }} {{ modalType === 'desktop' ? 'Desktop' : 'Mobile' }} Image
            </h3>

            <form @submit.prevent="submitForm" enctype="multipart/form-data">
              <div class="mb-4">
                <label class="block text-sm font-medium text-white mb-2">Title (Optional)</label>
                <input
                  v-model="form.title"
                  type="text"
                  class="w-full h-10 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                  placeholder="Enter image title"
                />
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium text-white mb-2">
                  Image Upload
                </label>
                <input
                  ref="imageInput"
                  @change="handleImageChange"
                  type="file"
                  accept="image/*"
                  class="w-full h-10 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100"
                  :required="!isEditing"
                />
                <p class="text-xs text-white/70 mt-1">
                  Max size: {{ modalType === 'desktop' ? '2MB' : '1MB' }}. Formats: JPEG, PNG, JPG, GIF, WebP
                  <span v-if="modalType === 'desktop'"> - Desktop version (5:2 ratio)</span>
                  <span v-else> - Mobile optimized (2:1 ratio)</span>
                </p>
                <div v-if="form.image" class="mt-2 text-sm text-green-400">
                  ✓ Image cropped and ready
                </div>
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium text-white mb-2">Sort Order</label>
                <input
                  v-model.number="form.sort_order"
                  type="number"
                  min="0"
                  class="w-full h-10 rounded-lg bg-white/50 border-0 focus:ring-2 focus:ring-cyan-400 text-slate-900 px-3 placeholder-slate-400 backdrop-blur-sm shadow-lg"
                  placeholder="0"
                />
              </div>

              <div class="flex justify-end space-x-2">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg text-sm hover:bg-white/30 text-white border border-white/30"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="loading"
                  class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded text-sm hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 disabled:opacity-50"
                >
                  {{ loading ? 'Uploading...' : (isEditing ? 'Update' : 'Upload') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Crop Modal -->
      <div
        v-if="showCropModal"
        class="fixed inset-0 bg-black bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
      >
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl p-6 w-full max-w-4xl mx-4 border border-white/20">
          <h3 class="text-lg font-semibold text-white mb-4">
            Crop Image for {{ modalType === 'desktop' ? 'Desktop (5:2)' : 'Mobile (2:1)' }}
          </h3>
          
          <div class="bg-white/10 rounded-lg p-4 mb-4">
            <canvas
              ref="cropCanvas"
              @mousedown="onMouseDown"
              @mousemove="onMouseMove"
              @mouseup="onMouseUp"
              @mouseleave="onMouseUp"
              class="max-w-full cursor-move border border-white/30 rounded"
            ></canvas>
          </div>
          
          <div class="text-sm text-white/80 mb-4">
            <p>• Drag the crop area to reposition</p>
            <p>• Drag the corner handles to resize (maintains aspect ratio)</p>
            <p>• {{ modalType === 'desktop' ? 'Desktop images use 5:2 aspect ratio' : 'Mobile images use 2:1 aspect ratio' }}</p>
          </div>
          
          <div class="flex justify-end space-x-3">
            <button
              @click="cancelCrop"
              class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg text-sm hover:bg-white/30 text-white border border-white/30"
            >
              Cancel
            </button>
            <button
              @click="cropAndSave"
              class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded text-sm hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200"
            >
              Crop & Continue
            </button>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-40 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="bg-gradient-to-br from-cyan-400/20 via-blue-500/15 to-indigo-600/20 backdrop-blur-xl rounded-2xl p-6 w-full max-w-sm mx-4 border border-white/20">
          <div class="text-center">
            <div class="w-12 h-12 mx-auto mb-4 bg-red-100/20 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">Delete Image</h3>
            <p class="text-white/80 mb-4">Are you sure you want to delete <strong>{{ deletingImage?.title || 'this image' }}</strong>? This action cannot be undone.</p>
            <div class="flex space-x-2">
              <button @click="cancelDelete" class="flex-1 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg text-sm hover:bg-white/30 text-white border border-white/30">
                Cancel
              </button>
              <button @click="confirmDelete" class="flex-1 px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded text-sm hover:from-red-600 hover:to-red-700 transform hover:scale-105 transition-all duration-200" :disabled="loading">
                {{ loading ? 'Deleting...' : 'Delete' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const page = usePage()

const desktopImages = ref([])
const mobileImages = ref([])
const showModal = ref(false)
const modalType = ref('desktop')
const isEditing = ref(false)
const editingImage = ref(null)
const loading = ref(false)

const form = ref({
  title: '',
  image: null,
  sort_order: 0
})

const imageInput = ref(null)
const showCropModal = ref(false)
const cropCanvas = ref(null)
const cropImage = ref(null)
const originalImage = ref(null)
const cropData = ref({
  x: 0,
  y: 0,
  width: 0,
  height: 0
})
const isDragging = ref(false)
const dragStart = ref({ x: 0, y: 0 })
const isResizing = ref(false)
const resizeHandle = ref('')
const showDeleteModal = ref(false)
const deletingImage = ref(null)

onMounted(() => {
  loadImages()
})

const loadImages = () => {
  router.reload({
    onSuccess: (page) => {
      desktopImages.value = page.props.desktopImages || []
      mobileImages.value = page.props.mobileImages || []
    }
  })
}

const showUploadModal = (type) => {
  modalType.value = type
  isEditing.value = false
  editingImage.value = null
  form.value = {
    title: '',
    image: null,
    sort_order: 0
  }
  showModal.value = true
}

const editImage = (image) => {
  modalType.value = image.type
  isEditing.value = true
  editingImage.value = image
  form.value = {
    title: image.title || '',
    sort_order: image.sort_order || 0
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  showCropModal.value = false
  form.value = {
    title: '',
    image: null,
    sort_order: 0
  }
  if (imageInput.value) imageInput.value.value = ''
}

const handleImageChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    originalImage.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      cropImage.value = e.target.result
      showCropModal.value = true
      nextTick(() => {
        initializeCrop()
      })
    }
    reader.readAsDataURL(file)
  }
}

const initializeCrop = () => {
  const img = new Image()
  img.onload = () => {
    const canvas = cropCanvas.value
    const ctx = canvas.getContext('2d')
    
    // Set canvas size to fit container
    const container = canvas.parentElement
    const maxWidth = container.clientWidth - 40
    const maxHeight = 400
    
    let canvasWidth = img.width
    let canvasHeight = img.height
    
    // Scale image to fit container
    if (canvasWidth > maxWidth) {
      canvasHeight = (canvasHeight * maxWidth) / canvasWidth
      canvasWidth = maxWidth
    }
    if (canvasHeight > maxHeight) {
      canvasWidth = (canvasWidth * maxHeight) / canvasHeight
      canvasHeight = maxHeight
    }
    
    canvas.width = canvasWidth
    canvas.height = canvasHeight
    
    // Draw image
    ctx.drawImage(img, 0, 0, canvasWidth, canvasHeight)
    
    // Initialize crop area based on type
    if (modalType.value === 'mobile') {
      // Mobile: 2:1 aspect ratio (matches h-40 = 160px height, mobile width ~320px)
      const cropWidth = Math.min(canvasWidth * 0.8, canvasHeight * 2)
      const cropHeight = cropWidth / 2
      cropData.value = {
        x: (canvasWidth - cropWidth) / 2,
        y: (canvasHeight - cropHeight) / 2,
        width: cropWidth,
        height: cropHeight
      }
    } else {
      // Desktop: 5:2 aspect ratio (matches h-64 = 256px height, desktop width ~1280px)
      const cropWidth = Math.min(canvasWidth * 0.8, canvasHeight * 5/2)
      const cropHeight = cropWidth * 2/5
      cropData.value = {
        x: (canvasWidth - cropWidth) / 2,
        y: (canvasHeight - cropHeight) / 2,
        width: cropWidth,
        height: cropHeight
      }
    }
    
    drawCropOverlay()
  }
  img.src = cropImage.value
}

const drawCropOverlay = () => {
  const canvas = cropCanvas.value
  const ctx = canvas.getContext('2d')
  
  // Redraw image
  const img = new Image()
  img.onload = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height)
    
    // Draw overlay
    ctx.fillStyle = 'rgba(0, 0, 0, 0.5)'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    
    // Clear crop area
    ctx.clearRect(cropData.value.x, cropData.value.y, cropData.value.width, cropData.value.height)
    ctx.drawImage(img, 
      cropData.value.x * (img.width / canvas.width), 
      cropData.value.y * (img.height / canvas.height),
      cropData.value.width * (img.width / canvas.width),
      cropData.value.height * (img.height / canvas.height),
      cropData.value.x, cropData.value.y, cropData.value.width, cropData.value.height
    )
    
    // Draw crop border
    ctx.strokeStyle = '#3b82f6'
    ctx.lineWidth = 2
    ctx.strokeRect(cropData.value.x, cropData.value.y, cropData.value.width, cropData.value.height)
    
    // Draw resize handles
    const handleSize = 8
    ctx.fillStyle = '#3b82f6'
    // Corner handles
    ctx.fillRect(cropData.value.x - handleSize/2, cropData.value.y - handleSize/2, handleSize, handleSize)
    ctx.fillRect(cropData.value.x + cropData.value.width - handleSize/2, cropData.value.y - handleSize/2, handleSize, handleSize)
    ctx.fillRect(cropData.value.x - handleSize/2, cropData.value.y + cropData.value.height - handleSize/2, handleSize, handleSize)
    ctx.fillRect(cropData.value.x + cropData.value.width - handleSize/2, cropData.value.y + cropData.value.height - handleSize/2, handleSize, handleSize)
  }
  img.src = cropImage.value
}

const onMouseDown = (event) => {
  const canvas = cropCanvas.value
  const rect = canvas.getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top
  
  const handleSize = 8
  
  // Check if clicking on resize handles
  if (Math.abs(x - cropData.value.x) < handleSize && Math.abs(y - cropData.value.y) < handleSize) {
    isResizing.value = true
    resizeHandle.value = 'nw'
  } else if (Math.abs(x - (cropData.value.x + cropData.value.width)) < handleSize && Math.abs(y - cropData.value.y) < handleSize) {
    isResizing.value = true
    resizeHandle.value = 'ne'
  } else if (Math.abs(x - cropData.value.x) < handleSize && Math.abs(y - (cropData.value.y + cropData.value.height)) < handleSize) {
    isResizing.value = true
    resizeHandle.value = 'sw'
  } else if (Math.abs(x - (cropData.value.x + cropData.value.width)) < handleSize && Math.abs(y - (cropData.value.y + cropData.value.height)) < handleSize) {
    isResizing.value = true
    resizeHandle.value = 'se'
  } else if (x >= cropData.value.x && x <= cropData.value.x + cropData.value.width && 
             y >= cropData.value.y && y <= cropData.value.y + cropData.value.height) {
    // Start dragging
    isDragging.value = true
    dragStart.value = { x: x - cropData.value.x, y: y - cropData.value.y }
  }
}

const onMouseMove = (event) => {
  const canvas = cropCanvas.value
  const rect = canvas.getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top
  
  if (isDragging.value) {
    const newX = Math.max(0, Math.min(x - dragStart.value.x, canvas.width - cropData.value.width))
    const newY = Math.max(0, Math.min(y - dragStart.value.y, canvas.height - cropData.value.height))
    
    cropData.value.x = newX
    cropData.value.y = newY
    drawCropOverlay()
  } else if (isResizing.value) {
    const aspectRatio = modalType.value === 'mobile' ? 1/2 : 2/5
    
    if (resizeHandle.value === 'se') {
      const newWidth = Math.max(50, x - cropData.value.x)
      const newHeight = newWidth * aspectRatio
      
      if (cropData.value.x + newWidth <= canvas.width && cropData.value.y + newHeight <= canvas.height) {
        cropData.value.width = newWidth
        cropData.value.height = newHeight
      }
    }
    drawCropOverlay()
  }
}

const onMouseUp = () => {
  isDragging.value = false
  isResizing.value = false
  resizeHandle.value = ''
}

const cropAndSave = () => {
  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')
  const img = new Image()
  
  img.onload = () => {
    // Calculate actual crop coordinates
    const scaleX = img.width / cropCanvas.value.width
    const scaleY = img.height / cropCanvas.value.height
    
    const actualX = cropData.value.x * scaleX
    const actualY = cropData.value.y * scaleY
    const actualWidth = cropData.value.width * scaleX
    const actualHeight = cropData.value.height * scaleY
    
    canvas.width = actualWidth
    canvas.height = actualHeight
    
    ctx.drawImage(img, actualX, actualY, actualWidth, actualHeight, 0, 0, actualWidth, actualHeight)
    
    canvas.toBlob((blob) => {
      form.value.image = new File([blob], originalImage.value.name, { type: originalImage.value.type })
      showCropModal.value = false
    }, originalImage.value.type, 0.9)
  }
  
  img.src = cropImage.value
}

const cancelCrop = () => {
  showCropModal.value = false
  form.value.image = null
  if (imageInput.value) imageInput.value.value = ''
}

const submitForm = () => {
  loading.value = true

  const formData = new FormData()
  formData.append('title', form.value.title)
  formData.append('type', modalType.value)
  formData.append('sort_order', form.value.sort_order)
  formData.append('_token', page.props.csrf_token)

  if (form.value.image) {
    formData.append('image', form.value.image)
  }

  if (isEditing.value) {
    formData.append('_method', 'PUT')
  }

  const url = isEditing.value
    ? `/admin/sliders/${editingImage.value.id}`
    : '/admin/sliders'

  router.post(url, formData, {
    onSuccess: () => {
      loading.value = false
      closeModal()
      loadImages()
    },
    onError: (errors) => {
      if (errors && (errors.message?.includes('419') || errors.status === 419)) {
        window.location.reload()
        return
      }
      loading.value = false
    }
  })
}

const toggleImage = (image) => {
  router.post(`/admin/sliders/${image.id}/toggle`, {
    _token: page.props.csrf_token
  }, {
    onSuccess: () => {
      loadImages()
    },
    onError: (errors) => {
      if (errors && (errors.message?.includes('419') || errors.status === 419)) {
        window.location.reload()
      }
    }
  })
}

const deleteImage = (image) => {
  deletingImage.value = image
  showDeleteModal.value = true
}

const confirmDelete = () => {
  router.delete(`/admin/sliders/${deletingImage.value.id}`, {
    data: {
      _token: page.props.csrf_token
    },
    onSuccess: () => {
      showDeleteModal.value = false
      deletingImage.value = null
      loadImages()
    },
    onError: (errors) => {
      if (errors && (errors.message?.includes('419') || errors.status === 419)) {
        window.location.reload()
      }
    }
  })
}

const cancelDelete = () => {
  showDeleteModal.value = false
  deletingImage.value = null
}
</script>
