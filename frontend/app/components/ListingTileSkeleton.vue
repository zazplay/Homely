<template>
  <div
    class="flex flex-col overflow-hidden rounded-[22px] border border-[#d9e3d1]"
    style="background:linear-gradient(#ffffff,#f5f8f2);box-shadow:inset 0 1px 0 #fff,0 1px 0 #cbd8c2,0 18px 36px -22px rgba(50,90,20,.35)"
    aria-hidden="true"
  >
    <div class="skeleton mx-2 mt-2 aspect-[4/3] rounded-2xl" />
    <div class="flex flex-col gap-2.5 px-4 pb-4 pt-3.5">
      <div class="skeleton h-6 w-1/2 rounded-lg" />
      <div class="skeleton h-[15px] w-[85%] rounded-md" />
      <div class="skeleton h-[13px] w-[60%] rounded-md" />
      <div class="flex gap-1.5">
        <div class="skeleton h-[22px] w-[50px] rounded-lg" />
        <div class="skeleton h-[22px] w-[70px] rounded-lg" />
      </div>
    </div>
  </div>
</template>
