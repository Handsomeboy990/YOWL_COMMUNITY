<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <Header />
  <div class="max-w-4xl mx-auto py-10 px-6">
    <h1 class="text-3xl font-bold text-[#1E2A38] mb-6">Frequently Asked Questions</h1>

    <div class="space-y-6">
      <div>
        <h2 class="font-semibold text-lg text-[#FF6B35]">1. What is YOWL Community?</h2>
        <p class="text-gray-700">YOWL is an interactive social platform where young people can share thoughts, reviews, and experiences while engaging with others in a safe environment.</p>
      </div>

      <div>
        <h2 class="font-semibold text-lg text-[#FF6B35]">2. Who can join YOWL?</h2>
        <p class="text-gray-700">YOWL is designed for users between the ages of 13 and 35. This ensures a generational balance where discussions remain relevant and engaging.</p>
      </div>

      <div>
        <h2 class="font-semibold text-lg text-[#FF6B35]">3. Can I post links, images, or media?</h2>
        <p class="text-gray-700">Yes, members can share links, upload images or videos, and add tags. All shared content must comply with our Community
          <em>
            <router-link to="/policy" class="hover:underline hover:text-[#FF6B35]">Policy</router-link>
          </em>

          .</p>
      </div>

      <div>
        <h2 class="font-semibold text-lg text-[#FF6B35]">4. How are inappropriate contents handled?</h2>
        <p class="text-gray-700">All reported content is reviewed by our moderation team. Violations of our rules may lead to warnings, content removal, or permanent suspension.</p>
      </div>

      <div>
        <h2 class="font-semibold text-lg text-[#FF6B35]">5. How do I report a member or post?</h2>
        <p class="text-gray-700">Every review or comment will have ( <strong>V1.2</strong> ) a "Report" option. Use it to notify moderators of any violation of our rules.</p>
      </div>

      <div>
        <h2 class="font-semibold text-lg text-[#FF6B35]">6. Can I suggest new features?</h2>
        <p class="text-gray-700">Absolutely! Visit the <em>
            <router-link to="/suggestion" class="hover:underline hover:text-[#FF6B35]">Any Suggestion ?</router-link>
          </em>
           page to share ideas on how we can improve <strong>YOWL Community</strong>.</p>
      </div>
    </div>
  </div>
  <Footer />
</template>

<script setup>
import Header from '../layouts/Header.vue';
import Footer from '../layouts/Footer.vue';
</script>
