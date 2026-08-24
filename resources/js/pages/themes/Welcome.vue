<script setup>
import { Head, Link } from '@/user/inertia';
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <div class="mb-6 flex gap-4">

            <!-- Login -->
            <Link
                href="/user/login"
                class="rounded-lg bg-indigo-600 px-6 py-2 text-white hover:bg-indigo-700 transition"
            >
                Login
            </Link>

            <!-- Register -->
            <Link
                href="/user/register"
                class="rounded-lg border border-indigo-600 px-6 py-2 text-indigo-600 hover:bg-indigo-50 transition"
            >
                Register
            </Link>

        </div>

        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row"
            >
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC]"
                >
                    <h1 class="mb-1 font-medium">Let's get started</h1>

                    <p class="mb-4 text-[#706f6c] dark:text-[#A1A09A]">
                        Laravel has an incredibly rich ecosystem.
                    </p>

                    <Link
                        href="/user/login"
                        class="inline-block rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                    >
                        Go to Login →
                    </Link>

                </div>

                <div
                    class="relative -mb-px aspect-335/376 w-full shrink-0 overflow-hidden rounded-t-lg bg-[#fff2f2] lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:bg-[#1D0002]"
                >
                </div>
            </main>
        </div>
    </div>
</template>