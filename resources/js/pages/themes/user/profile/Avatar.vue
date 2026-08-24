<script setup>
import PageLayout from '../../layouts/PageLayout.vue'
import { useForm, usePage } from '@/user/inertia'

const props = defineProps({
  avatars: Array,
})

const page = usePage()
const currentId = page.props.auth.user?.image

const form = useForm({
  image: currentId,
})

const selectAvatar = (id) => {
  form.image = id
  form.post(route('user.profile.avatar.update'), {
    preserveScroll: true,
  })
}
</script>

<template>
  <PageLayout class="main" title="Avatar Settings" :showBack="true" :backUrl="route('user.profile.setting-center')">
    <div class="avatar-container">
      <div class="avatar-container-content">
        <div class="van-grid" style="padding-left:10px;">
          <div v-for="a in avatars" :key="a.id" class="van-grid-item" style="flex-basis:33.3333%; padding-right:10px; margin-top:10px;">
            <div class="van-grid-item__content van-grid-item__content--center" @click="selectAvatar(a.id)">
              <img :class="{ active: a.id === page.props.auth.user?.image }" :src="'/' + a.path" alt="avatar" />
              <div v-if="a.id === page.props.auth.user?.image">
                <div role="checkbox" class="van-checkbox" tabindex="0" aria-checked="true">
                  <div class="van-checkbox__icon van-checkbox__icon--round van-checkbox__icon--checked" style="font-size:20px;">
                    <i class="van-badge__wrapper van-icon van-icon-success"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </PageLayout>
</template>

<style scoped>
.avatar-container { padding: .32rem .4rem; }
.avatar-container-content .van-grid-item { width: 2.66667rem; height: 2.4rem; }
.avatar-container-content img { width: 2.66667rem; height: 2.4rem; border-radius: .26667rem; }
.avatar-container-content img.active { border-color: var(--main-color); border-width: .08rem; border-style: solid; }
.avatar-container-content .van-checkbox { position: absolute; bottom: .26667rem; right: .26667rem; }
.van-grid-item__content { position: relative; cursor: pointer; }
</style>