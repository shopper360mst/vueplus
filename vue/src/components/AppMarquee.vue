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
  background-size: auto 75%; /* 75% of 40px is 30px height */
  background-position: center;
  width: calc(100% + 138.8px); /* Container width + one tile width (30px * 620/134) */
  height: 100%;
  animation: marque 2s linear infinite;
  will-change: transform;
  backface-visibility: hidden;

  @media (min-width: 768px) {
    animation: marque 4s linear infinite;
  }
}

@keyframes marque {
  0% { transform: translate3d(0, 0, 0); }
  100% { transform: translate3d(-138.8px, 0, 0); }
}
</style>
