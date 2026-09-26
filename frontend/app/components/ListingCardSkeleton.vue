<template>
  <div class="card flex flex-col overflow-hidden rounded-3xl" aria-hidden="true">
    <div class="skeleton mx-2 mt-2 aspect-[4/3] rounded-[18px]" />
    <div class="flex flex-col gap-2.5 px-[18px] pb-[18px] pt-4">
      <div class="skeleton h-[26px] w-[55%] rounded-lg" />
      <div class="skeleton h-4 w-[85%] rounded-md" />
      <div class="skeleton h-3.5 w-[65%] rounded-md" />
      <div class="flex gap-1.5">
        <div class="skeleton h-6 w-[52px] rounded-[9px]" />
        <div class="skeleton h-6 w-[52px] rounded-[9px]" />
        <div class="skeleton h-6 w-[70px] rounded-[9px]" />
      </div>
      <div class="mt-2 flex items-center gap-2.5 border-t border-[#e3eadc] pt-3">
        <div class="skeleton size-[30px] shrink-0 rounded-full" />
        <div class="skeleton h-3.5 flex-1 rounded-md" />
        <div class="skeleton h-[30px] w-[72px] rounded-[10px]" />
      </div>
    </div>
  </div>
</template>
