<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <Header />
  <div class="max-w-4xl mx-auto py-10 pb-25 px-6">
    <h1 class="text-3xl font-bold text-[#1E2A38] mb-6">Community Policy</h1>

    <p class="mb-4 text-gray-700">
      YOWL Community is a safe digital space designed exclusively for users between the ages of <strong>13 and 35</strong>.
      By joining our platform, you <strong>agree</strong>  to follow the rules outlined below. Failure to comply may result in <strong>account suspension or permanent removal</strong>.
    </p>

    <h2 class="font-semibold text-lg text-[#FF6B35] mt-6">1. Age Restriction</h2>
    <p class="text-gray-700 mb-4">
      Our platform is strictly reserved for individuals between the ages of 13 and 35. Users who misrepresent their age or exceed the age limit will be removed without notice. This policy ensures that conversations remain relevant and engaging for our target demographic.
    </p>

    <h2 class="font-semibold text-lg text-[#FF6B35] mt-6">2. Respectful Communication</h2>
    <p class="text-gray-700 mb-4">
      Hate speech, harassment, discrimination, or offensive content of any kind is not tolerated. We expect all members to engage respectfully and maintain a welcoming environment.
    </p>

    <h2 class="font-semibold text-lg text-[#FF6B35] mt-6">3. Content Sharing</h2>
    <p class="text-gray-700 mb-4">
      Members may post reviews, images, videos, and external links. However, all content must:
      <ul class="list-disc ml-6 mt-2">
        <li>Respect copyright laws.</li>
        <li>Be safe for a community of young users (no nudity, explicit, or violent material).</li>
        <li>Stay relevant to constructive discussion and knowledge sharing.</li>
      </ul>
    </p>

    <h2 class="font-semibold text-lg text-[#FF6B35] mt-6">4. Privacy & Security</h2>
    <p class="text-gray-700 mb-4">
      Members must protect their personal information. Sharing sensitive data such as addresses, phone numbers, or banking details is strictly prohibited. YOWL will never request such information from you directly through reviews or posts.
    </p>

    <h2 class="font-semibold text-lg text-[#FF6B35] mt-6">5. Moderation & Enforcement</h2>
    <p class="text-gray-700 mb-4">
      YOWL reserves the right to:
      <ul class="list-disc ml-6 mt-2">
        <li>Remove any content that violates these policies.</li>
        <li>Issue warnings to offending members.</li>
        <li>Expel members permanently if rules are repeatedly violated.</li>
      </ul>
    </p>

    <h2 class="font-semibold text-lg text-[#FF6B35] mt-6">6. Final Note</h2>
    <p class="text-gray-700">
      By participating in YOWL Community, you commit to maintaining a safe and inspiring environment for everyone. Our goal is to create a dynamic, respectful, and enriching platform for young people.
    </p>
  </div>
  <Footer />
</template>


<script setup>
import Footer from '../layouts/Footer.vue';
import Header from '../layouts/Header.vue';

</script>
