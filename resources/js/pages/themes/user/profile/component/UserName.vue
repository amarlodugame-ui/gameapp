<script setup>
import { usePage } from '@/user/inertia'
const page = usePage()
const name = page.props.auth.user.name
</script>

<template>{{ name }}</template>