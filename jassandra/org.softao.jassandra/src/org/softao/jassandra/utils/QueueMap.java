package org.softao.jassandra.utils;

import java.util.ArrayList;
import java.util.Collection;
import java.util.Comparator;
import java.util.List;
import java.util.Map;
import java.util.NavigableMap;
import java.util.NavigableSet;
import java.util.Set;
import java.util.SortedMap;
import java.util.TreeMap;

/**
 * A Navigable map which is sorted based on the sequence they are added.
 * 
 * @param <K>
 * @param <V>
 */
public class QueueMap<K, V> implements NavigableMap<K, V> {
	private final List<K> mList;
	private final TreeMap<K, V> mMap;

	/**
	 * 
	 */
	public QueueMap() {
		mList = new ArrayList<K>();
		mMap = new TreeMap<K, V>(new Comparator<K>() {
			@Override
			public int compare(K o1, K o2) {
				int index1 = mList.indexOf(o1);
				int index2 = mList.indexOf(o2);
				if (index1 < 0) {
					return 1; // o1 has not been added, so bigger!
				} else {
					return index1 - index2;
				}
			}
		});
	}

	@Override
	public Entry<K, V> ceilingEntry(K key) {
		return mMap.ceilingEntry(key);
	}

	@Override
	public K ceilingKey(K key) {
		return mMap.ceilingKey(key);
	}

	@Override
	public NavigableSet<K> descendingKeySet() {
		return mMap.descendingKeySet();
	}

	@Override
	public NavigableMap<K, V> descendingMap() {
		return mMap.descendingMap();
	}

	@Override
	public Entry<K, V> firstEntry() {
		return mMap.firstEntry();
	}

	@Override
	public java.util.Map.Entry<K, V> floorEntry(K key) {
		return mMap.floorEntry(key);
	}

	@Override
	public K floorKey(K key) {
		return mMap.floorKey(key);
	}

	@Override
	public SortedMap<K, V> headMap(K toKey) {
		return mMap.headMap(toKey);
	}

	@Override
	public NavigableMap<K, V> headMap(K toKey, boolean inclusive) {
		return mMap.headMap(toKey, inclusive);
	}

	@Override
	public Map.Entry<K, V> higherEntry(K key) {
		return mMap.higherEntry(key);
	}

	@Override
	public K higherKey(K key) {
		return mMap.higherKey(key);
	}

	@Override
	public Map.Entry<K, V> lastEntry() {
		return mMap.lastEntry();
	}

	@Override
	public Map.Entry<K, V> lowerEntry(K key) {
		return mMap.lowerEntry(key);
	}

	@Override
	public K lowerKey(K key) {
		return mMap.lowerKey(key);
	}

	@Override
	public NavigableSet<K> navigableKeySet() {
		return mMap.navigableKeySet();
	}

	@Override
	public Map.Entry<K, V> pollFirstEntry() {
		return mMap.pollFirstEntry();
	}

	@Override
	public Map.Entry<K, V> pollLastEntry() {
		return mMap.pollFirstEntry();
	}

	@Override
	public SortedMap<K, V> subMap(K fromKey, K toKey) {
		return mMap.subMap(fromKey, toKey);
	}

	@Override
	public NavigableMap<K, V> subMap(K fromKey, boolean fromInclusive, K toKey,
			boolean toInclusive) {
		return mMap.subMap(fromKey, fromInclusive, toKey, toInclusive);
	}

	@Override
	public SortedMap<K, V> tailMap(K fromKey) {
		return mMap.tailMap(fromKey);
	}

	@Override
	public NavigableMap<K, V> tailMap(K fromKey, boolean inclusive) {
		return mMap.tailMap(fromKey, inclusive);
	}

	@Override
	public Comparator<? super K> comparator() {
		return mMap.comparator();
	}

	@Override
	public Set<Entry<K, V>> entrySet() {
		return mMap.entrySet();
	}

	@Override
	public K firstKey() {
		return mMap.firstKey();
	}

	@Override
	public Set<K> keySet() {
		return mMap.keySet();
	}

	@Override
	public K lastKey() {
		return mMap.lastKey();
	}

	@Override
	public Collection<V> values() {
		return mMap.values();
	}

	@Override
	public void clear() {
		mMap.clear();
	}

	@Override
	public boolean containsKey(Object key) {
		return mMap.containsKey(key);
	}

	@Override
	public boolean containsValue(Object value) {
		return mMap.containsValue(value);
	}

	@Override
	public V get(Object key) {
		return mMap.get(key);
	}

	@Override
	public boolean isEmpty() {
		return mMap.isEmpty();
	}

	@Override
	public V put(K key, V value) {
		V result = mMap.put(key, value);
		if (mList.contains(key)) {
			mList.remove(key);
		}
		mList.add(key);
		return result;
	}

	@Override
	public void putAll(Map<? extends K, ? extends V> m) {
		for (K key : m.keySet()) {
			put(key, m.get(key));
		}
	}

	@Override
	public V remove(Object key) {
		mList.remove(key);
		return mMap.remove(key);
	}

	@Override
	public int size() {
		return mMap.size();
	}
}
