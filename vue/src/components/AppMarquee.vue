<script setup>
</script>

<template>
<div class="w-full h-[40px] bg-secondary overflow-hidden relative">
    <div class="marque-stripe absolute top-0 left-0"></div>
</div>
</template>

<style scoped>
.marque-stripe {
  background-image: url('../assets/images/content_stripe.png');
  background-repeat: repeat-x;
  background-size: 138.806px 30px; /* Exact tile dimensions */
  background-position: 0 center;
  width: 100%;
  height: 100%;
  animation: marque 3s linear infinite;

  @media (min-width: 768px) {
    animation: marque 6s linear infinite;
  }
}

@keyframes marque {
  from { background-position: 0 center; }
  to { background-position: -138.806px center; }
}
</style>
