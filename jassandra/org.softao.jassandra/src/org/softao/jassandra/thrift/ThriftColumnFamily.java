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

import org.apache.cassandra.thrift.Column;
import org.apache.cassandra.thrift.ColumnOrSuperColumn;
import org.apache.cassandra.thrift.ColumnPath;
import org.apache.cassandra.thrift.InvalidRequestException;
import org.apache.cassandra.thrift.Mutation;
import org.apache.cassandra.thrift.SuperColumn;
import org.apache.cassandra.thrift.TimedOutException;
import org.apache.cassandra.thrift.UnavailableException;
import org.apache.cassandra.thrift.Cassandra.Client;
import org.apache.thrift.TException;
import org.softao.jassandra.IColumn;
import org.softao.jassandra.IColumnFamily;
import org.softao.jassandra.ICriteria;
import org.softao.jassandra.IKeySpace;
import org.softao.jassandra.JassandraException;

import com.google.common.collect.Lists;

/**
 * An implementation of the {@link IColumnFamily}, which is used internally for
 * <code>Thrift</code> implementation.
 */
class ThriftColumnFamily implements IColumnFamily {
	private final ThriftKeySpace mKeySpace;
	private final String mName;
	private final boolean mIsSuper;

	/**
	 * @param keySpace
	 * @param name
	 * @param isSuper
	 * @throws JassandraException
	 */
	public ThriftColumnFamily(ThriftKeySpace keySpace, String name,
			boolean isSuper) throws JassandraException {
		if (keySpace == null) {
			throw new NullPointerException("keySpace");
		}
		if (name == null) {
			throw new NullPointerException("name");
		}
		
		mKeySpace = keySpace;
		mName = name;
		mIsSuper = isSuper;
	}

	@Override
	public IKeySpace getKeySpace() {
		return mKeySpace;
	}

	@Override
	public String getName() {
		return mName;
	}

	@Override
	public boolean isSuper() {
		return mIsSuper;
	}

	@Override
	public ICriteria createCriteria() throws JassandraException {
		return new ThriftCriteria(this);
	}

	@Override
	public void delete(String key, IColumn column) throws JassandraException {
		Client client = getClient();
		String ksName = mKeySpace.getName();
		if (column.isSuper()) {
			ColumnPath cp = new ColumnPath(mName);
			cp.setSuper_column(column.getName().toByteArray());
			for (IColumn child : column.getColumns()) {
				cp.setColumn(child.getName().toByteArray());
				try {
					client.remove(ksName, key, cp, column.getTimestamp(),
							getConsistencyLevel());
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
		} else {
			ColumnPath cp = new ColumnPath(mName);
			cp.setColumn(column.getName().toByteArray());
			try {
				client.remove(ksName, key, cp, column.getTimestamp(),
						getConsistencyLevel());
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
	}

	@Override
	public void insert(String key, IColumn... columns)
			throws JassandraException {
		ArrayList<IColumn> list = Lists.newArrayList(columns);
		insert(key, list);
	}

	@Override
	public void insert(String key, List<IColumn> columns)
			throws JassandraException {
		if (columns == null) {
			throw new NullPointerException("columns");
		}
		if (columns.size() == 0) {
			return;
		}

		Map<String, Map<String, List<Mutation>>> mapMutation =
				new HashMap<String, Map<String, List<Mutation>>>();
		List<Mutation> listMutation = new ArrayList<Mutation>();
		Map<String, List<Mutation>> mapCFMutation =
				new HashMap<String, List<Mutation>>();
		mapCFMutation.put(mName, listMutation);
		mapMutation.put(key, mapCFMutation);

		for (IColumn column : columns) {
			if (isSuper()) {
				if (!column.isSuper()) {
					throw new JassandraException(
							"Super column family need super columns.");
				}
				SuperColumn sc = new SuperColumn();
				sc.name = column.getName().toByteArray();
				for (IColumn child : column.getColumns()) {
					Column colChild = new Column(child.getName().toByteArray(),
							child.getValue().toByteArray(), child
									.getTimestamp());
					sc.addToColumns(colChild);
				}
				ColumnOrSuperColumn csc = new ColumnOrSuperColumn();
				csc.super_column = sc;

				Mutation mutation = new Mutation();
				mutation.setColumn_or_supercolumn(csc);
				listMutation.add(mutation);
			} else {
				if (column.isSuper()) {
					throw new JassandraException(
							"Only super column family need super columns.");
				}
				Column colChild = new Column(column.getName().toByteArray(),
						column.getValue().toByteArray(), column.getTimestamp());
				ColumnOrSuperColumn csc = new ColumnOrSuperColumn();
				csc.column = colChild;
				Mutation mutation = new Mutation();
				mutation.setColumn_or_supercolumn(csc);
				listMutation.add(mutation);
			}
		}
		try {
			Client client = getClient();
			String ksName = getKeySpace().getName();
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
	
	private Client getClient() {
		return ((ThriftConnection) mKeySpace.getConnection()).getClient();
	}

	private org.apache.cassandra.thrift.ConsistencyLevel getConsistencyLevel() {
		int value = ((ThriftConnection) mKeySpace.getConnection())
				.getConsistencyLevel().getValue();
		return org.apache.cassandra.thrift.ConsistencyLevel.findByValue(value);
	}

}
