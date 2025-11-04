import { reactive } from 'vue';

export const formEditState = reactive({
  form: null,
  services: null,
  categories: null,
  staff: null,
  // Cache for the main form list page
  formListCache: {
    forms: [],
    allServices: [],
    allCategories: [],
    allStaff: [],
    lastFetched: null,
  },
});
