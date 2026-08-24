<script setup>
import PageLayout from '../../layouts/PageLayout.vue'

defineProps({
  sections: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <PageLayout title="Beginner's Guide" :showBack="true" :backUrl="route('user.account')">
    <div class="guide-page">
      <div class="guide-card">
        <div class="guide-container-content">
          <section
            v-for="(section, sectionIndex) in sections"
            :key="sectionIndex"
            class="guide-section"
          >
            <h2 class="guide-title">{{ section.title }}</h2>

            <p
              v-for="(text, textIndex) in section.texts"
              :key="textIndex"
              class="guide-text"
            >
              {{ text }}
            </p>

            <div
              :class="section.images.length > 1 ? 'guide-image-row' : 'guide-image-single'"
            >
              <img
                v-for="(image, imageIndex) in section.images"
                :key="imageIndex"
                :src="image"
                :alt="`${section.title} Image ${imageIndex + 1}`"
                class="guide-image"
                :class="section.imageClass"
              />
            </div>
          </section>
        </div>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
.guide-page {
  padding: 16px;
  background: #f7f7f7;
  min-height: 100vh;
}

.guide-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 20px 16px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(245, 204, 44, 0.15);
}

.guide-container-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.guide-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.guide-title {
  margin: 0;
  padding: 12px 16px;
  border-radius: 12px;
  background: var(--main_gradient-color);
  color: #222;
  font-size: 22px;
  font-weight: 700;
  line-height: 1.4;
  box-shadow: 0 4px 12px rgba(245, 204, 44, 0.28);
}

.guide-text {
  margin: 0;
  padding: 12px 14px;
  background: #fffdf4;
  border-left: 4px solid var(--main-color);
  border-radius: 10px;
  color: #333;
  font-size: 16px;
  font-weight: 500;
  line-height: 1.7;
}

.guide-image-single {
  display: flex;
  justify-content: center;
}

.guide-image-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 16px;
}

.guide-image {
  display: block;
  width: 100%;
  height: auto;
  margin-top: 6px;
  border-radius: 16px;
  border: 1px solid rgba(245, 204, 44, 0.18);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
  background: #fff;
  padding: 6px;
}

.guide-image-large {
  max-width: 541px;
}

.guide-image-medium {
  max-width: 400px;
}

.guide-image-small {
  max-width: 390px;
}

@media (max-width: 768px) {
  .guide-page {
    padding: 12px;
  }

  .guide-card {
    padding: 16px 12px;
    border-radius: 16px;
  }

  .guide-title {
    font-size: 18px;
    padding: 10px 12px;
  }

  .guide-text {
    font-size: 15px;
    padding: 10px 12px;
  }

  .guide-image-large,
  .guide-image-medium,
  .guide-image-small {
    max-width: 100%;
  }
}
</style>