/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra.thrift;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.NavigableMap;
import java.util.Map.Entry;

import org.apache.cassandra.thrift.ColumnOrSuperColumn;
import org.apache.cassandra.thrift.ColumnParent;
import org.apache.cassandra.thrift.Deletion;
import org.apache.cassandra.thrift.InvalidRequestException;
import org.apache.cassandra.thrift.KeyRange;
import org.apache.cassandra.thrift.KeySlice;
import org.apache.cassandra.thrift.Mutation;
import org.apache.cassandra.thrift.SlicePredicate;
import org.apache.cassandra.thrift.SliceRange;
import org.apache.cassandra.thrift.TimedOutException;
import org.apache.cassandra.thrift.UnavailableException;
import org.apache.cassandra.thrift.Cassandra.Client;
import org.apache.thrift.TException;
import org.softao.jassandra.ByteArray;
import org.softao.jassandra.IColumn;
import org.softao.jassandra.ICriteria;
import org.softao.jassandra.JassandraException;
import org.softao.jassandra.utils.QueueMap;

import com.google.common.collect.Lists;

/**
 * An implementation of {@link ICriteria}, which is used internally for
 * <code>Thrift</code> implementation.
 * <p>
 * This class is not thread-safe.
 */
class ThriftCriteria implements ICriteria {
	private final ThriftColumnFamily mColumnFamily;

	/* Token Criterion: range only */
	private String mTokenStart = null;
	private String mTokenEnd = null;

	/* Key Criterion: list or range */
	private List<String> mKeyList = null;
	private String mKeyStart = null;
	private String mKeyEnd = null;
	private int mKeyCount = -1;

	/* Column criteria: list or range */
	private ByteArray mColumnStart = ByteArray.EMPTY;
	private ByteArray mColumnEnd = ByteArray.EMPTY;
	private int mColumnCount = -1;
	private boolean mReversed = false;
	private ByteArray mSuperColumnName = ByteArray.EMPTY;
	private List<ByteArray> mColumnNames;

	/**
	 * @param columnFamily
	 */
	public ThriftCriteria(ThriftColumnFamily columnFamily) {
		if (columnFamily == null) {
			throw new NullPointerException("columnFamily");
		}
		
		mColumnFamily = columnFamily;
	}

	@Override
	public ICriteria keyList(List<String> keys) throws JassandraException {
		if (mKeyList != null) {
			mKeyList.clear();
		} else {
			mKeyList = new ArrayList<String>();
		}
		mKeyList.addAll(keys);
		mKeyStart = null;
		mKeyEnd = null;
		mKeyCount = -1;
		return this;
	}

	@Override
	public ICriteria keyList(String... keys) throws JassandraException {
		return this.keyList(Lists.newArrayList(keys));
	}

	@Override
	public ICriteria keyRange(String start, String end, int count)
			throws JassandraException {
		if (count <= 0) {
			throw new IllegalArgumentException(
					"count cannot be negative value.");
		}

		mKeyList = null;
		mKeyStart = start;
		mKeyEnd = end;
		mKeyCount = count;
		return this;
	}

	@Override
	public ICriteria tokenRange(String start, String end)
			throws JassandraException {
		mTokenStart = start;
		mTokenEnd = end;
		return this;
	}

	@Override
	public ICriteria columnList(List<ByteArray> columnNames)
			throws JassandraException {
		if (mColumnNames != null) {
			mColumnNames.clear();
		} else {
			mColumnNames = new ArrayList<ByteArray>();
		}

		mColumnNames.addAll(columnNames);
		mColumnStart = null;
		mColumnEnd = null;
		mColumnCount = -1;
		return this;
	}

	@Override
	public ICriteria columnRange(ByteArray startColumnName,
			ByteArray endColumnName, int count) throws JassandraException {
		if (mColumnNames != null) {
			mColumnNames = null;
		}
		mColumnStart = startColumnName;
		mColumnEnd = endColumnName;
		mColumnCount = count;

		return this;
	}

	@Override
	public NavigableMap<String, List<IColumn>> select() throws JassandraException {
		if (mKeyList != null) {
			if (mKeyList.size() > 1
					&& (mTokenStart != null || mTokenEnd != null)) {
				throw new JassandraException(
						"Select only supports token range while query more than one key.");
			}
		} else {
			if (mKeyStart == null || mKeyEnd == null || mKeyCount < 0) {
				throw new JassandraException("key has not been set.");
			}
		}
		if (mColumnNames == null && mColumnCount < 0) {
			throw new JassandraException("columns or slice has not been set.");
		}

		String ksName = mColumnFamily.getKeySpace().getName();

		ColumnParent parent = new ColumnParent(mColumnFamily.getName());
		if (mSuperColumnName != null && mSuperColumnName != ByteArray.EMPTY) {
			parent.setSuper_column(mSuperColumnName.toByteArray());
		}

		SlicePredicate predicate = new SlicePredicate();
		if (mColumnNames != null) {
			List<byte[]> list = new ArrayList<byte[]>();
			for (ByteArray ba : mColumnNames) {
				list.add(ba.toByteArray());
			}
			predicate.setColumn_names(list);
		} else {
			byte[] start = mColumnStart.toByteArray();
			byte[] end = mColumnEnd.toByteArray();
			SliceRange sr = new SliceRange(start, end, mReversed, mColumnCount);
			predicate.setSlice_range(sr);
		}

		KeyRange keyRange = new KeyRange();
		if (mTokenStart != null || mTokenEnd != null) {
			keyRange.start_token = mTokenStart;
			keyRange.end_token = mTokenEnd;
		}
		if (mKeyList != null) {
			keyRange.start_key = mKeyList.get(0);
			keyRange.end_key = mKeyList.get(0);
			keyRange.count = 1;
		} else {
			keyRange.start_key = mKeyStart;
			keyRange.end_key = mKeyEnd;
			keyRange.count = mKeyCount;
		}

		try {
			Client client = getClient();
			if (mKeyList != null && mKeyList.size() > 1) {
				Map<String, List<ColumnOrSuperColumn>> reply = client
						.multiget_slice(ksName, mKeyList, parent, predicate,
								getConsistencyLevel());
				NavigableMap<String, List<IColumn>> result =
					new QueueMap<String, List<IColumn>>();
				for (Entry<String, List<ColumnOrSuperColumn>> entry : reply
						.entrySet()) {
					result.put(entry.getKey(), asColumnList(entry.getValue()));
				}
				return result;
			} else {
				List<KeySlice> reply = client.get_range_slices(ksName, parent,
						predicate, keyRange, getConsistencyLevel());

				NavigableMap<String, List<IColumn>> result = 
					new QueueMap<String, List<IColumn>>();
				if (reply != null) {
					for (KeySlice keySlice : reply) {
						String key = keySlice.getKey();
						List<IColumn> list = asColumnList(keySlice.getColumns());
						result.put(key, list);
					}
				}
				return result;
			}
		} catch (InvalidRequestException e) {
			throw new JassandraException("Access cassandra failed.", e);
		} catch (UnavailableException e) {
			throw new JassandraException("Access cassandra failed.", e);
		} catch (TimedOutException e) {
			throw new JassandraException("Access cassandra failed.", e);
		} catch (TException e) {
			throw new JassandraException("Access cassandra failed.", e);
		}
	}

	private List<IColumn> asColumnList(List<ColumnOrSuperColumn> columns) {
		if (columns == null) {
			return null;
		}

		List<IColumn> result = Lists.newArrayList();
		for (ColumnOrSuperColumn csc : columns) {
			if (csc.super_column != null) {
				result.add(new ThriftColumn(csc.super_column));
			} else {
				result.add(new ThriftColumn(csc.column));
			}
		}
		return result;
	}

	private Client getClient() {
		return ((ThriftConnection) mColumnFamily.getKeySpace().getConnection())
				.getClient();
	}

	private org.apache.cassandra.thrift.ConsistencyLevel getConsistencyLevel() {
		int value = ((ThriftConnection) mColumnFamily.getKeySpace()
				.getConnection()).getConsistencyLevel().getValue();
		return org.apache.cassandra.thrift.ConsistencyLevel.findByValue(value);
	}

	@Override
	public ICriteria reverse() throws JassandraException {
		mReversed = true;
		return this;
	}

	@Override
	public void delete() throws JassandraException {
		if (mKeyList == null || mKeyList.size() > 1) {
			throw new JassandraException("Delete only supports single key.");
		}
		if (mColumnNames == null) {
			throw new JassandraException("Delete only support column list.");
		}

		Deletion deletion = new Deletion();
		if (mSuperColumnName != null && mSuperColumnName != ByteArray.EMPTY) {
			deletion.setSuper_column(mSuperColumnName.toByteArray());
		}
		if (mColumnNames != null) {
			SlicePredicate predicate = new SlicePredicate();
			List<byte[]> list = new ArrayList<byte[]>();
			for (ByteArray ba : mColumnNames) {
				list.add(ba.toByteArray());
			}
			predicate.setColumn_names(list);
			deletion.setPredicate(predicate);
		}

		deletion.setTimestamp(System.currentTimeMillis());

		Mutation mutation = new Mutation();
		mutation.setDeletion(deletion);

		List<Mutation> listMutation = new ArrayList<Mutation>();
		listMutation.add(mutation);

		Map<String, List<Mutation>> mapCFMutation =
				new HashMap<String, List<Mutation>>();
		mapCFMutation.put(mColumnFamily.getName(), listMutation);

		Map<String, Map<String, List<Mutation>>> mapMutation =
				new HashMap<String, Map<String, List<Mutation>>>();
		mapMutation.put(mKeyList.get(0), mapCFMutation);

		String ksName = mColumnFamily.getKeySpace().getName();
		try {
			Client client = getClient();
			client.batch_mutate(ksName, mapMutation, getConsistencyLevel());
		} catch (InvalidRequestException e) {
			throw new JassandraException("Access cassandra failed.", e);
		} catch (UnavailableException e) {
			throw new JassandraException("Access cassandra failed.", e);
		} catch (TimedOutException e) {
			throw new JassandraException("Access cassandra failed.", e);
		} catch (TException e) {
			throw new JassandraException("Access cassandra failed.", e);
		}
	}

	@Override
	public int count() throws JassandraException {
		if (mKeyList == null || mKeyList.size() > 1) {
			throw new JassandraException("Count only supports single key.");
		}

		if (mTokenStart != null || mTokenEnd != null) {
			throw new JassandraException("Count does not support token range.");
		}

		if (mColumnNames != null) {
			throw new JassandraException("Count does not support column list.");
		}

		String ksName = mColumnFamily.getKeySpace().getName();
		String key = mKeyList.get(0);
		ColumnParent parent = new ColumnParent(mColumnFamily.getName());
		if (mSuperColumnName != null && mSuperColumnName != ByteArray.EMPTY) {
			parent.setSuper_column(mSuperColumnName.toByteArray());
		}
		try {
			Client client = getClient();
			return client.get_count(ksName, key, parent, getConsistencyLevel());
		} catch (InvalidRequestException e) {
			throw new JassandraException("Access cassandra failed.", e);
		} catch (UnavailableException e) {
			throw new JassandraException("Access cassandra failed.", e);
		} catch (TimedOutException e) {
			throw new JassandraException("Access cassandra failed.", e);
		} catch (TException e) {
			throw new JassandraException("Access cassandra failed.", e);
		}
	}

	@Override
	public ICriteria superColumn(ByteArray superColumnName)
			throws JassandraException {
		mSuperColumnName = superColumnName;
		return this;
	}
}
