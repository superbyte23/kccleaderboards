<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="min-h-screen bg-[#1a1a1a] text-white font-sans p-4 md:p-10 flex flex-col lg:flex-row gap-8 items-start justify-center">
  
  <div class="w-full max-w-4xl bg-[#121212] rounded-[2.5rem] p-6 md:p-12 shadow-2xl border border-white/5">
    
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 gap-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center border border-yellow-500/50">
           <img src="https://api.dicebear.com/7.x/bottts/svg?seed=Lucky" class="w-6 h-6" alt="logo">
        </div>
        <h1 class="text-xl font-bold text-[#e5b64b]">European Cup Inu</h1>
      </div>
      
      <div class="flex items-center gap-3">
        <div class="bg-[#1a1a1a] border border-yellow-500/30 px-4 py-2 rounded-full text-xs font-mono text-yellow-500/80 flex items-center gap-2">
          Bx4fWW...pUMDrc
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 9l-7 7-7-7" /></svg>
        </div>
        <button class="bg-[#f3ba2f] hover:bg-[#e2ac28] text-black font-bold py-2 px-8 rounded-xl transition-all">
          Log in
        </button>
      </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
      
      <div class="space-y-4">
        <div class="relative bg-[#3d3d3d] rounded-l-3xl rounded-r-full p-6 flex items-center gap-6 group hover:translate-x-2 transition-transform cursor-pointer">
          <div class="absolute -top-2 -left-2 text-xl opacity-40">👑</div>
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" class="w-20 h-20 rounded-full bg-[#555]" alt="avatar">
          <div>
            <div class="flex items-baseline gap-2">
              <span class="text-5xl font-bold tracking-tighter text-white/90">45</span>
              <span class="text-sm text-gray-400 font-semibold">$ECI</span>
            </div>
            <p class="text-[10px] text-gray-400 font-mono mb-1">Bx4fWW...pUMDrc</p>
            <p class="text-xs font-bold text-gray-300">5 wins <span class="mx-1 opacity-30">|</span> 3 losses</p>
          </div>
        </div>

        <div class="relative bg-[#6b6141] rounded-l-3xl rounded-r-[6rem] p-10 flex items-center gap-6 group hover:translate-x-2 transition-transform cursor-pointer">
          <div class="absolute -top-2 -left-2 text-xl text-yellow-500 opacity-60">👑</div>
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Aneka" class="w-24 h-24 rounded-full bg-[#837651]" alt="avatar">
          <div>
            <div class="flex items-baseline gap-2">
              <span class="text-6xl font-bold tracking-tighter text-white">45</span>
              <span class="text-sm text-yellow-200/50 font-semibold">$ECI</span>
            </div>
            <p class="text-[10px] text-yellow-100/40 font-mono mb-1">Bx4fWW...pUMDrc</p>
            <p class="text-xs font-bold text-white/80">5 wins <span class="mx-1 opacity-30">|</span> 3 losses</p>
          </div>
        </div>

        <div class="relative bg-[#4a3a3a] rounded-l-3xl rounded-r-full p-6 flex items-center gap-6 group hover:translate-x-2 transition-transform cursor-pointer">
          <div class="absolute -top-2 -left-2 text-xl opacity-30">👑</div>
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Sasha" class="w-20 h-20 rounded-full bg-[#5f4b4b]" alt="avatar">
          <div>
            <div class="flex items-baseline gap-2">
              <span class="text-5xl font-bold tracking-tighter text-white/90">45</span>
              <span class="text-sm text-gray-400 font-semibold">$ECI</span>
            </div>
            <p class="text-[10px] text-gray-400 font-mono mb-1">Bx4fWW...pUMDrc</p>
            <p class="text-xs font-bold text-gray-300">5 wins <span class="mx-1 opacity-30">|</span> 3 losses</p>
          </div>
        </div>
      </div>

      <div class="space-y-3">
        <h3 class="text-xl font-medium mb-6 text-gray-200">Those who <br/> have predicted more</h3>
        
        <div class="flex items-center gap-3">
          <span class="text-gray-500 font-bold w-6 text-center">04</span>
          <div class="flex-1 bg-[#222] rounded-2xl p-3 flex justify-between items-center border border-white/5">
            <div class="flex items-center gap-3">
              <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Leo" class="w-10 h-10 rounded-xl bg-blue-400" alt="user">
              <div class="flex items-center gap-2 bg-[#1a1a1a] px-3 py-1 rounded-lg border border-white/10">
                 <span class="text-[10px] font-mono text-gray-400">Bx4fWW...pUMDrc</span>
              </div>
            </div>
            <span class="text-green-500 font-bold text-xs">5 wins</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="text-gray-500 font-bold w-6 text-center">05</span>
          <div class="flex-1 bg-[#222] rounded-2xl p-3 flex justify-between items-center border border-white/5">
            <div class="flex items-center gap-3">
              <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Max" class="w-10 h-10 rounded-xl bg-orange-400" alt="user">
              <div class="flex items-center gap-2 bg-[#1a1a1a] px-3 py-1 rounded-lg border border-white/10">
                 <span class="text-[10px] font-mono text-gray-400">Bx4fWW...pUMDrc</span>
              </div>
            </div>
            <span class="text-green-500 font-bold text-xs">5 wins</span>
          </div>
        </div>

        <div class="opacity-30 blur-[1px] pointer-events-none">
          <div class="flex items-center gap-3">
            <span class="text-gray-500 font-bold w-6 text-center">06</span>
            <div class="flex-1 bg-[#222] rounded-2xl p-3"></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>