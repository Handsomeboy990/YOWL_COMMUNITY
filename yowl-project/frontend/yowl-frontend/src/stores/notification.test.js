import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useNotificationStore } from './notification'
import api from '@/services/apiService'

vi.mock('@/services/apiService', () => ({
  default: {
    get: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

const notification = (id, readAt = null) => ({
  id,
  read_at: readAt,
  created_at: '2026-08-12T10:00:00.000000Z',
  data: { type: 'comment', title: 'Nouveau commentaire', body: 'Quelqu\'un a commenté.', url: '/reviews/1' },
})

describe('NotificationStore', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useNotificationStore()
    vi.clearAllMocks()
  })

  it('charge la liste et le compteur non lu', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { data: [notification('a'), notification('b', '2026-08-12T11:00:00Z')] }, unread_count: 1 },
    })

    await store.fetchNotifications()

    expect(store.items).toHaveLength(2)
    expect(store.unreadCount).toBe(1)
    expect(store.hasUnread).toBe(true)
    expect(store.loading).toBe(false)
    expect(store.loaded).toBe(true)
  })

  it('expose un message quand le chargement echoue', async () => {
    api.get.mockRejectedValueOnce({ response: { data: { message: 'Service indisponible.' } } })

    await store.fetchNotifications()

    expect(store.error).toBe('Service indisponible.')
    expect(store.loading).toBe(false)
  })

  it('marque une notification comme lue et decremente le compteur', async () => {
    api.get.mockResolvedValueOnce({ data: { data: { data: [notification('a')] }, unread_count: 1 } })
    await store.fetchNotifications()

    api.patch.mockResolvedValueOnce({ data: { data: { unread_count: 0 } } })
    await store.markAsRead('a')

    expect(api.patch).toHaveBeenCalledWith('/notifications/a/read')
    expect(store.items[0].read_at).not.toBeNull()
    expect(store.unreadCount).toBe(0)
  })

  it('ne rappelle pas le serveur pour une notification deja lue', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { data: [notification('a', '2026-08-12T11:00:00Z')] }, unread_count: 0 },
    })
    await store.fetchNotifications()

    await store.markAsRead('a')

    expect(api.patch).not.toHaveBeenCalled()
  })

  it('marque tout comme lu', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { data: [notification('a'), notification('b')] }, unread_count: 2 },
    })
    await store.fetchNotifications()

    api.patch.mockResolvedValueOnce({ data: {} })
    await store.markAllAsRead()

    expect(store.unreadCount).toBe(0)
    expect(store.items.every((item) => item.read_at)).toBe(true)
  })

  it('supprime une notification non lue et ajuste le compteur', async () => {
    api.get.mockResolvedValueOnce({ data: { data: { data: [notification('a')] }, unread_count: 1 } })
    await store.fetchNotifications()

    api.delete.mockResolvedValueOnce({ data: {} })
    await store.remove('a')

    expect(store.items).toHaveLength(0)
    expect(store.unreadCount).toBe(0)
  })

  it('garde le dernier compteur connu si le rafraichissement echoue', async () => {
    api.get.mockResolvedValueOnce({ data: { data: { data: [notification('a')] }, unread_count: 3 } })
    await store.fetchNotifications()

    api.get.mockRejectedValueOnce(new Error('reseau'))
    await store.fetchUnreadCount()

    expect(store.unreadCount).toBe(3)
  })

  it('remet le store a zero', async () => {
    api.get.mockResolvedValueOnce({ data: { data: { data: [notification('a')] }, unread_count: 1 } })
    await store.fetchNotifications()

    store.reset()

    expect(store.items).toHaveLength(0)
    expect(store.unreadCount).toBe(0)
    expect(store.loaded).toBe(false)
  })
})
