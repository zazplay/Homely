<template>
  <!-- Thin lime progress bar at the top while a page / its data is loading. -->
  <NuxtLoadingIndicator color="linear-gradient(90deg,#dcfca2,#8fd84a 50%,#6fc22f)" :height="3" :throttle="80" />
  <NuxtRouteAnnouncer />
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>
