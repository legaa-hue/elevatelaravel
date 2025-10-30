<template>
    <div class="inline-flex items-center relative group">
        <!-- Info Icon -->
        <button
            type="button"
            @mouseenter="showTooltip = true"
            @mouseleave="showTooltip = false"
            @click="showTooltip = !showTooltip"
            class="ml-1 text-gray-400 hover:text-blue-600 focus:outline-none transition-all duration-200 transform hover:scale-110"
            :class="iconClass"
        >
            <svg 
                class="w-5 h-5" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path 
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    stroke-width="2" 
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
        </button>

        <!-- Tooltip Popup -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 transform scale-95"
            enter-to-class="opacity-100 transform scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 transform scale-100"
            leave-to-class="opacity-0 transform scale-95"
        >
            <div
                v-if="showTooltip"
                :class="[
                    'absolute z-50 px-4 py-3 text-sm bg-gray-900 text-white rounded-lg shadow-xl max-w-xs md:max-w-sm lg:max-w-md',
                    positionClass
                ]"
                style="width: max-content; max-width: 90vw;"
            >
                <!-- Title -->
                <div v-if="title" class="font-semibold mb-1 text-base">
                    {{ title }}
                </div>
                
                <!-- Content -->
                <div class="text-gray-200 leading-relaxed">
                    <slot>{{ content }}</slot>
                </div>

                <!-- Arrow -->
                <div 
                    :class="[
                        'absolute w-3 h-3 bg-gray-900 transform rotate-45',
                        arrowPositionClass
                    ]"
                ></div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    content: {
        type: String,
        default: ''
    },
    title: {
        type: String,
        default: ''
    },
    position: {
        type: String,
        default: 'top', // top, bottom, left, right
        validator: (value) => ['top', 'bottom', 'left', 'right'].includes(value)
    },
    iconClass: {
        type: String,
        default: ''
    }
});

const showTooltip = ref(false);

const positionClass = computed(() => {
    const positions = {
        top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
        bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
        left: 'right-full top-1/2 -translate-y-1/2 mr-2',
        right: 'left-full top-1/2 -translate-y-1/2 ml-2'
    };
    return positions[props.position];
});

const arrowPositionClass = computed(() => {
    const positions = {
        top: 'top-full left-1/2 -translate-x-1/2 -mt-1.5',
        bottom: 'bottom-full left-1/2 -translate-x-1/2 -mb-1.5',
        left: 'left-full top-1/2 -translate-y-1/2 -ml-1.5',
        right: 'right-full top-1/2 -translate-y-1/2 -mr-1.5'
    };
    return positions[props.position];
});
</script>
